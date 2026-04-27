import datetime as dt
import json
import os
import platform
import shutil
import socket
from typing import Any

try:
    import psutil  # type: ignore
except ImportError:  # pragma: no cover - runtime dependency fallback
    psutil = None


def round_one(value: float | int | None) -> float | None:
    if value is None:
        return None

    return round(float(value), 1)


def computer_name() -> str:
    return platform.node() or os.environ.get("COMPUTERNAME") or socket.gethostname() or "UNKNOWN-HOST"


def cpu_percent() -> float:
    if psutil is None:
        return 0.0

    return round(float(psutil.cpu_percent(interval=0.35)), 1)


def memory_snapshot() -> tuple[int, int]:
    if psutil is None:
        return (0, 1)

    memory = psutil.virtual_memory()
    total_mb = max(1, round(memory.total / 1024 / 1024))
    used_mb = max(0, round(memory.used / 1024 / 1024))

    return (used_mb, total_mb)


def fixed_mounts() -> list[str]:
    if psutil is None:
        system_drive = os.environ.get("SystemDrive", "C:") + "\\"
        return [system_drive]

    mounts: list[str] = []

    for partition in psutil.disk_partitions(all=False):
        if "cdrom" in partition.opts.lower():
            continue

        mountpoint = partition.mountpoint
        if mountpoint and mountpoint not in mounts:
            mounts.append(mountpoint)

    if not mounts:
        mounts.append(os.environ.get("SystemDrive", "C:") + "\\")

    return mounts


def disk_snapshot() -> tuple[float, float, float, float]:
    mounts = fixed_mounts()
    system_drive = os.environ.get("SystemDrive", "C:") + "\\"
    primary_mount = system_drive if system_drive in mounts else mounts[0]

    try:
        primary_usage = shutil.disk_usage(primary_mount)
        disk_total_gb = max(1.0, round(primary_usage.total / (1024 ** 3), 1))
        disk_used_gb = max(0.0, round((primary_usage.total - primary_usage.free) / (1024 ** 3), 1))
    except OSError:
        disk_total_gb = 1.0
        disk_used_gb = 0.0

    storage_total = 0
    storage_free = 0

    for mount in mounts:
        try:
            usage = shutil.disk_usage(mount)
        except OSError:
            continue

        storage_total += usage.total
        storage_free += usage.free

    if storage_total <= 0:
        storage_total = int(disk_total_gb * (1024 ** 3))
        storage_free = max(0, int((disk_total_gb - disk_used_gb) * (1024 ** 3)))

    return (
        disk_used_gb,
        disk_total_gb,
        round(storage_free / (1024 ** 3), 1),
        round(storage_total / (1024 ** 3), 1),
    )


def uptime_seconds() -> int | None:
    if psutil is None:
        return None

    try:
        return max(0, int(dt.datetime.now().timestamp() - psutil.boot_time()))
    except (OSError, ValueError):
        return None


def temperature_c() -> float | None:
    if psutil is None or not hasattr(psutil, "sensors_temperatures"):
        return None

    try:
        sensors = psutil.sensors_temperatures(fahrenheit=False) or {}
    except (AttributeError, OSError, NotImplementedError):
        return None

    candidates: list[float] = []

    for readings in sensors.values():
        for reading in readings:
            current = getattr(reading, "current", None)

            if current is None:
                continue

            numeric = float(current)

            if -30 <= numeric <= 150:
                candidates.append(numeric)

    if not candidates:
        return None

    return round(sum(candidates) / len(candidates), 1)


def _preferred_interface(stats: dict[str, Any], addresses: dict[str, Any]) -> str | None:
    preferred_name = None
    preferred_score = -10_000

    for name, info in stats.items():
        if not info.isup:
            continue

        ipv4 = None
        for address in addresses.get(name, []):
            if getattr(address, "family", None) == socket.AF_INET and address.address and not address.address.startswith("127."):
                ipv4 = address.address
                break

        score = 0
        lowered = name.lower()

        if ipv4:
            score += 100

        if info.speed and info.speed > 0:
            score += min(int(info.speed / 100), 20)

        for hint in ("ethernet", "wi-fi", "wifi", "wlan"):
            if hint in lowered:
                score += 15

        for hint in ("loopback", "virtual", "vmware", "hyper-v", "vethernet", "bluetooth", "teredo", "wsl"):
            if hint in lowered:
                score -= 100

        if score > preferred_score:
            preferred_score = score
            preferred_name = name

    return preferred_name


def network_snapshot() -> dict[str, Any]:
    if psutil is None:
        return {
            "networkConnected": False,
            "networkName": "Disconnected",
            "networkSpeedMbps": None,
            "networkIpv4": None,
            "networkMac": None,
            "networkRxBytes": None,
            "networkTxBytes": None,
        }

    stats = psutil.net_if_stats()
    addresses = psutil.net_if_addrs()
    counters = psutil.net_io_counters(pernic=True)
    interface_name = _preferred_interface(stats, addresses)

    if interface_name is None:
        return {
            "networkConnected": False,
            "networkName": "Disconnected",
            "networkSpeedMbps": None,
            "networkIpv4": None,
            "networkMac": None,
            "networkRxBytes": None,
            "networkTxBytes": None,
        }

    ipv4 = None
    mac = None

    for address in addresses.get(interface_name, []):
        if getattr(address, "family", None) == socket.AF_INET and address.address and not address.address.startswith("127."):
            ipv4 = address.address
        if str(getattr(address, "family", "")) in {"AddressFamily.AF_LINK", "-1", "17"} and address.address:
            mac = address.address

    stat = stats.get(interface_name)
    counter = counters.get(interface_name)

    return {
        "networkConnected": bool(stat and stat.isup and ipv4),
        "networkName": interface_name,
        "networkSpeedMbps": int(stat.speed) if stat and stat.speed > 0 else None,
        "networkIpv4": ipv4,
        "networkMac": mac,
        "networkRxBytes": float(counter.bytes_recv) if counter else None,
        "networkTxBytes": float(counter.bytes_sent) if counter else None,
    }


def collect_telemetry() -> dict[str, Any]:
    ram_used_mb, ram_total_mb = memory_snapshot()
    disk_used_gb, disk_total_gb, storage_free_gb, storage_total_gb = disk_snapshot()

    return {
        "computerName": computer_name(),
        "cpuPercent": cpu_percent(),
        "ramUsedMb": ram_used_mb,
        "ramTotalMb": ram_total_mb,
        "diskUsedGb": disk_used_gb,
        "diskTotalGb": disk_total_gb,
        "storageFreeGb": storage_free_gb,
        "storageTotalGb": storage_total_gb,
        "temperatureC": temperature_c(),
        "uptimeSeconds": uptime_seconds(),
        "sampledAt": dt.datetime.now().astimezone().isoformat(),
        **network_snapshot(),
    }


def main() -> None:
    print(json.dumps(collect_telemetry(), separators=(",", ":")))


if __name__ == "__main__":
    main()
