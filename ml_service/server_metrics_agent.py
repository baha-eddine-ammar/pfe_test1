import argparse
import json
import sys
import time
import urllib.error
import urllib.request
from pathlib import Path

from workstation_telemetry import collect_telemetry


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Continuously send workstation telemetry to the Laravel server monitoring API."
    )
    parser.add_argument("--endpoint", required=True, help="Full POST URL for /api/server-metrics")
    parser.add_argument("--identifier", required=True, help="Registered server identifier")
    parser.add_argument("--token", required=True, help="API token for the registered server")
    parser.add_argument("--interval", type=float, default=15.0, help="Seconds between samples")
    parser.add_argument("--timeout", type=float, default=10.0, help="HTTP timeout in seconds")
    parser.add_argument("--once", action="store_true", help="Collect and submit a single sample, then exit")
    parser.add_argument(
        "--queue-file",
        default=str(Path(__file__).with_name("server_metrics_queue.jsonl")),
        help="Path to a local durable queue used when the API is temporarily unreachable",
    )

    return parser.parse_args()


def build_payload(identifier: str, previous_sample: dict | None = None) -> tuple[dict, dict]:
    telemetry = collect_telemetry()
    sample = {
        "rx_bytes": telemetry.get("networkRxBytes"),
        "tx_bytes": telemetry.get("networkTxBytes"),
        "captured_at": time.time(),
    }

    download_mbps = None
    upload_mbps = None

    if previous_sample and sample["rx_bytes"] is not None and sample["tx_bytes"] is not None:
        elapsed = max(1.0, sample["captured_at"] - float(previous_sample.get("captured_at", sample["captured_at"])))
        received_bytes = max(0.0, float(sample["rx_bytes"]) - float(previous_sample.get("rx_bytes", sample["rx_bytes"])))
        sent_bytes = max(0.0, float(sample["tx_bytes"]) - float(previous_sample.get("tx_bytes", sample["tx_bytes"])))
        download_mbps = round((received_bytes * 8) / elapsed / 1_000_000, 2)
        upload_mbps = round((sent_bytes * 8) / elapsed / 1_000_000, 2)

    payload = {
        "identifier": identifier,
        "cpu_percent": telemetry["cpuPercent"],
        "ram_used_mb": telemetry["ramUsedMb"],
        "ram_total_mb": telemetry["ramTotalMb"],
        "disk_used_gb": telemetry["diskUsedGb"],
        "disk_total_gb": telemetry["diskTotalGb"],
        "storage_free_gb": telemetry["storageFreeGb"],
        "storage_total_gb": telemetry["storageTotalGb"],
        "temperature_c": telemetry.get("temperatureC"),
        "net_rx_mbps": download_mbps,
        "net_tx_mbps": upload_mbps,
        "network_connected": telemetry.get("networkConnected"),
        "network_name": telemetry.get("networkName"),
        "network_speed_mbps": telemetry.get("networkSpeedMbps"),
        "network_ipv4": telemetry.get("networkIpv4"),
        "uptime_seconds": telemetry.get("uptimeSeconds"),
        "sampled_at": telemetry["sampledAt"],
    }

    return ({key: value for key, value in payload.items() if value is not None}, sample)


def post_sample(endpoint: str, token: str, payload: dict, timeout: float) -> tuple[int, str]:
    request = urllib.request.Request(
        endpoint,
        data=json.dumps(payload).encode("utf-8"),
        headers={
            "Content-Type": "application/json",
            "Accept": "application/json",
            "X-Server-Token": token,
        },
        method="POST",
    )

    with urllib.request.urlopen(request, timeout=timeout) as response:
        body = response.read().decode("utf-8", errors="replace")
        return response.status, body


def log(message: str, *, stream=sys.stdout) -> None:
    timestamp = time.strftime("%Y-%m-%d %H:%M:%S")
    print(f"[{timestamp}] {message}", file=stream, flush=True)


def append_queue(queue_file: Path, payload: dict) -> None:
    queue_file.parent.mkdir(parents=True, exist_ok=True)
    with queue_file.open("a", encoding="utf-8") as handle:
        handle.write(json.dumps(payload) + "\n")


def load_queue(queue_file: Path) -> list[dict]:
    if not queue_file.exists():
        return []

    queued: list[dict] = []

    with queue_file.open("r", encoding="utf-8") as handle:
        for line in handle:
            line = line.strip()
            if not line:
                continue
            try:
                queued.append(json.loads(line))
            except json.JSONDecodeError:
                continue

    return queued


def save_queue(queue_file: Path, payloads: list[dict]) -> None:
    if not payloads:
        if queue_file.exists():
            queue_file.unlink()
        return

    queue_file.parent.mkdir(parents=True, exist_ok=True)
    with queue_file.open("w", encoding="utf-8") as handle:
        for payload in payloads:
            handle.write(json.dumps(payload) + "\n")


def flush_queue(endpoint: str, token: str, timeout: float, queue_file: Path) -> bool:
    queued = load_queue(queue_file)

    if not queued:
        return True

    remaining: list[dict] = []

    for index, payload in enumerate(queued):
        try:
            status, body = post_sample(endpoint, token, payload, timeout)
            log(f"Flushed queued telemetry sample ({status}). Response: {body}")
        except urllib.error.HTTPError as exc:
            remaining = queued[index:]
            body = exc.read().decode("utf-8", errors="replace")
            log(f"Queue flush failed with HTTP {exc.code}. Response: {body}", stream=sys.stderr)
            break
        except urllib.error.URLError as exc:
            remaining = queued[index:]
            log(f"Queue flush failed: {exc.reason}", stream=sys.stderr)
            break

    if remaining:
        save_queue(queue_file, remaining)
        return False

    save_queue(queue_file, [])
    return True


def main() -> int:
    args = parse_args()
    endpoint = args.endpoint.strip()
    identifier = args.identifier.strip()
    token = args.token.strip()
    queue_file = Path(args.queue_file)
    interval = max(5.0, args.interval)
    previous_sample = None

    try:
        while True:
            payload, previous_sample = build_payload(identifier, previous_sample)

            try:
                flush_queue(endpoint, token, args.timeout, queue_file)
                status, body = post_sample(endpoint, token, payload, args.timeout)
                log(f"Submitted telemetry sample ({status}) for {identifier}. Response: {body}")
            except urllib.error.HTTPError as exc:
                body = exc.read().decode("utf-8", errors="replace")
                append_queue(queue_file, payload)
                log(
                    f"Telemetry submission failed with HTTP {exc.code} for {identifier}. Queued locally. Response: {body}",
                    stream=sys.stderr,
                )
            except urllib.error.URLError as exc:
                append_queue(queue_file, payload)
                log(f"Telemetry submission failed for {identifier}. Queued locally: {exc.reason}", stream=sys.stderr)

            if args.once:
                return 0

            time.sleep(interval)
    except KeyboardInterrupt:
        log("Telemetry agent stopped by user.")
        return 0


if __name__ == "__main__":
    raise SystemExit(main())
