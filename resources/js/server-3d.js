import * as THREE from 'three';
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader.js';

const MODEL_CANDIDATES = [
    '/models/server.glb',
    '/models/server_v2_console.glb',
];

const clamp = (value, min, max) => Math.min(Math.max(value, min), max);

export function initServer3D() {
    const container = document.getElementById('server-3d');

    if (!container || container.dataset.server3dMounted === 'true') {
        return;
    }

    container.dataset.server3dMounted = 'true';

    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(26, 1, 0.1, 100);
    camera.position.set(0, 0.45, 8.2);

    const renderer = new THREE.WebGLRenderer({
        antialias: true,
        alpha: true,
        powerPreference: 'high-performance',
    });

    renderer.outputColorSpace = THREE.SRGBColorSpace;
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.08;
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = THREE.PCFSoftShadowMap;
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 1.8));
    renderer.domElement.className = 'h-full w-full';

    container.appendChild(renderer.domElement);

    scene.add(new THREE.AmbientLight(0xffffff, 2.2));

    const keyLight = new THREE.DirectionalLight(0xffffff, 3.6);
    keyLight.position.set(5.8, 7.2, 6.2);
    keyLight.castShadow = true;
    keyLight.shadow.mapSize.set(1024, 1024);
    scene.add(keyLight);

    const fillLight = new THREE.PointLight(0x93c5fd, 16, 18, 2);
    fillLight.position.set(-5.4, 2.1, 4.8);
    scene.add(fillLight);

    const rimLight = new THREE.PointLight(0x67e8f9, 10, 18, 2);
    rimLight.position.set(2.6, 4.2, -4.2);
    scene.add(rimLight);

    const floor = new THREE.Mesh(
        new THREE.CircleGeometry(3.9, 72),
        new THREE.ShadowMaterial({ color: 0x60a5fa, opacity: 0.12 }),
    );
    floor.rotation.x = -Math.PI / 2;
    floor.position.y = -1.78;
    floor.receiveShadow = true;
    scene.add(floor);

    const halo = new THREE.Mesh(
        new THREE.TorusGeometry(2.45, 0.038, 24, 120),
        new THREE.MeshBasicMaterial({ color: 0x93c5fd, transparent: true, opacity: 0.16 }),
    );
    halo.rotation.x = Math.PI / 2;
    halo.position.y = floor.position.y + 0.08;
    scene.add(halo);

    const modelGroup = new THREE.Group();
    scene.add(modelGroup);

    const loader = new GLTFLoader();

    let frameId = null;
    let modelReady = false;
    let model = null;
    let lastTick = 0;
    let lastWidth = 0;
    let lastHeight = 0;
    let disposed = false;

    const basePosition = { x: 0.12, y: -0.22 };
    const targetRotation = { x: -0.05, y: -0.55 };
    const currentRotation = { x: -0.05, y: -0.55 };
    const targetOffset = { x: 0, y: 0 };
    const currentOffset = { x: 0, y: 0 };
    let targetZoom = 8.2;
    let currentZoom = 8.2;

    let isDragging = false;
    let dragStartX = 0;
    let dragStartY = 0;
    let dragRotationX = targetRotation.x;
    let dragRotationY = targetRotation.y;

    function setRendererSize() {
        const width = Math.max(container.clientWidth || 1, 1);
        const height = Math.max(container.clientHeight || 1, 1);

        if (width === lastWidth && height === lastHeight) {
            return;
        }

        lastWidth = width;
        lastHeight = height;
        camera.aspect = width / height;
        camera.updateProjectionMatrix();
        renderer.setSize(width, height, false);
    }

    function enhanceMaterials(object3D) {
        object3D.traverse((child) => {
            if (!child.isMesh) {
                return;
            }

            child.castShadow = true;
            child.receiveShadow = true;

            if (child.material) {
                child.material.envMapIntensity = 1.15;

                if ('emissive' in child.material) {
                    child.material.emissive = child.material.emissive.clone().add(new THREE.Color(0x10284f));
                    child.material.emissiveIntensity = 0.1;
                }
            }
        });
    }

    function centerAndScaleModel(object3D) {
        const bounds = new THREE.Box3().setFromObject(object3D);
        const size = bounds.getSize(new THREE.Vector3());
        const center = bounds.getCenter(new THREE.Vector3());

        object3D.position.sub(center);

        const maxAxis = Math.max(size.x, size.y, size.z) || 1;
        const scale = 2.35 / maxAxis;

        object3D.scale.setScalar(scale);
        object3D.position.set(basePosition.x, basePosition.y, 0);

        floor.position.y = -(size.y * scale) / 2 - 0.18;
        halo.position.y = floor.position.y + 0.08;
    }

    function getModelPaths() {
        const configuredPath = container.dataset.modelPath?.trim();

        return configuredPath
            ? [configuredPath, ...MODEL_CANDIDATES.filter((path) => path !== configuredPath)]
            : MODEL_CANDIDATES;
    }

    function loadModel(paths, index = 0) {
        if (index >= paths.length) {
            console.error('[Server3D] Failed to load model. Tried:', paths);
            return;
        }

        loader.load(
            paths[index],
            (gltf) => {
                model = gltf.scene;
                enhanceMaterials(model);
                centerAndScaleModel(model);
                modelGroup.add(model);
                modelReady = true;
            },
            undefined,
            (error) => {
                console.error(`[Server3D] Failed to load model from ${paths[index]}.`, error);
                loadModel(paths, index + 1);
            },
        );
    }

    function onPointerMove(event) {
        const bounds = container.getBoundingClientRect();
        const relativeX = ((event.clientX - bounds.left) / bounds.width) - 0.5;
        const relativeY = ((event.clientY - bounds.top) / bounds.height) - 0.5;

        if (isDragging) {
            const deltaX = (event.clientX - dragStartX) / bounds.width;
            const deltaY = (event.clientY - dragStartY) / bounds.height;

            targetRotation.y = dragRotationY + (deltaX * Math.PI * 0.9);
            targetRotation.x = clamp(dragRotationX + (deltaY * Math.PI * 0.22), -0.32, 0.14);
            return;
        }

        targetOffset.x = relativeX * 0.22;
        targetOffset.y = relativeY * -0.18;
        targetRotation.y = -0.55 + (relativeX * 0.35);
        targetRotation.x = -0.05 + (relativeY * -0.1);
    }

    function onPointerDown(event) {
        isDragging = true;
        dragStartX = event.clientX;
        dragStartY = event.clientY;
        dragRotationX = targetRotation.x;
        dragRotationY = targetRotation.y;
        container.setPointerCapture?.(event.pointerId);
    }

    function endDrag(event) {
        isDragging = false;
        container.releasePointerCapture?.(event.pointerId);
    }

    function onPointerLeave() {
        if (isDragging) {
            return;
        }

        targetOffset.x = 0;
        targetOffset.y = 0;
        targetRotation.x = -0.05;
        targetRotation.y = -0.55;
    }

    function onWheel(event) {
        event.preventDefault();
        targetZoom = clamp(targetZoom + (event.deltaY * 0.0035), 6.9, 9.8);
    }

    function renderFrame(timestamp = 0) {
        if (disposed) {
            return;
        }

        const delta = (timestamp - lastTick) / 1000 || 0.016;
        lastTick = timestamp;
        const time = timestamp * 0.001;

        setRendererSize();

        currentRotation.x = THREE.MathUtils.lerp(currentRotation.x, targetRotation.x, 0.08);
        currentRotation.y = THREE.MathUtils.lerp(currentRotation.y, targetRotation.y, 0.08);
        currentOffset.x = THREE.MathUtils.lerp(currentOffset.x, targetOffset.x, 0.06);
        currentOffset.y = THREE.MathUtils.lerp(currentOffset.y, targetOffset.y, 0.06);
        currentZoom = THREE.MathUtils.lerp(currentZoom, targetZoom, 0.08);

        if (modelReady && model) {
            modelGroup.rotation.x = currentRotation.x;
            modelGroup.rotation.y = currentRotation.y + (time * 0.08);
            modelGroup.position.x = basePosition.x + currentOffset.x;
            modelGroup.position.y = basePosition.y + currentOffset.y + (Math.sin(time * 1.2) * 0.12);
        }

        camera.position.x = currentOffset.x * 0.55;
        camera.position.y = 0.5 + (currentOffset.y * 0.35);
        camera.position.z = currentZoom;
        camera.lookAt(0.08, 0.02, 0);

        halo.rotation.z += delta * 0.12;

        renderer.render(scene, camera);
        frameId = window.requestAnimationFrame(renderFrame);
    }

    function destroy() {
        disposed = true;

        if (frameId) {
            window.cancelAnimationFrame(frameId);
        }

        container.removeEventListener('pointermove', onPointerMove);
        container.removeEventListener('pointerdown', onPointerDown);
        container.removeEventListener('pointerup', endDrag);
        container.removeEventListener('pointercancel', endDrag);
        container.removeEventListener('pointerleave', onPointerLeave);
        container.removeEventListener('wheel', onWheel);
        window.removeEventListener('resize', setRendererSize);
        renderer.dispose();
    }

    loadModel(getModelPaths());
    setRendererSize();

    container.addEventListener('pointermove', onPointerMove);
    container.addEventListener('pointerdown', onPointerDown);
    container.addEventListener('pointerup', endDrag);
    container.addEventListener('pointercancel', endDrag);
    container.addEventListener('pointerleave', onPointerLeave);
    container.addEventListener('wheel', onWheel, { passive: false });
    window.addEventListener('resize', setRendererSize);
    window.addEventListener('beforeunload', destroy, { once: true });

    frameId = window.requestAnimationFrame(renderFrame);
}
