/**
 * Kustomizer - 3D Product Customization JavaScript
 * Main class for handling 3D STL product customization
 */

// Check for required dependencies
if (typeof THREE === 'undefined') {
    console.error('Kustomizer: THREE.js is required but not loaded');
}

class Kustomizer {
    constructor(options = {}) {
        // Validate THREE.js availability
        if (typeof THREE === 'undefined') {
            this.showError('THREE.js library is required but not loaded.');
            return;
        }
        
        // Get container
        if (typeof options === 'string') {
            this.container = document.getElementById(options);
        } else if (options.container) {
            this.container = typeof options.container === 'string' ? 
                document.getElementById(options.container) : options.container;
        } else {
            this.container = document.getElementById('kustomizer-viewer');
        }
        
        if (!this.container) {
            console.error('Kustomizer: Container element not found');
            return;
        }
        
        // Configuration options
        this.options = {
            stlFile: options.stlFile || '',
            defaultTexture: options.defaultTexture || '',
            allowTextCustomization: options.allowTextCustomization || true,
            allowSVGUpload: options.allowSVGUpload || true,
            allowTextureUpload: options.allowTextureUpload || true,
            maxTextLength: options.maxTextLength || 100,
            availableFonts: options.availableFonts || ['Arial', 'Helvetica', 'Times New Roman'],
            ...options
        };
        
        // Three.js components
        this.scene = null;
        this.camera = null;
        this.renderer = null;
        this.controls = null;
        this.stlMesh = null;
        this.textMeshes = [];
        this.svgMeshes = [];
        
        // Customization data
        this.customization = {
            texture: null,
            textElements: [],
            svgElements: [],
            layout: null
        };
        
        this.init();
    }
    
    init() {
        this.setupScene();
        this.loadSTL();
        this.setupEventListeners();
        this.animate();
    }
    
    setupScene() {
        // Scene
        this.scene = new THREE.Scene();
        this.scene.background = new THREE.Color(0xf0f0f0);
        
        // Camera
        const containerRect = this.container.getBoundingClientRect();
        this.camera = new THREE.PerspectiveCamera(
            75, 
            containerRect.width / containerRect.height, 
            0.1, 
            1000
        );
        this.camera.position.set(0, 0, 100);
        
        // Renderer
        this.renderer = new THREE.WebGLRenderer({ antialias: true, preserveDrawingBuffer: true });
        this.renderer.setPixelRatio(window.devicePixelRatio);
        this.renderer.setSize(containerRect.width, containerRect.height);
        this.renderer.shadowMap.enabled = true;
        this.renderer.shadowMap.type = THREE.PCFSoftShadowMap;
        this.container.appendChild(this.renderer.domElement);
        
        // Controls
        this.controls = new THREE.OrbitControls(this.camera, this.renderer.domElement);
        this.controls.enableDamping = true;
        this.controls.dampingFactor = 0.05;
        this.controls.enableZoom = true;
        this.controls.enablePan = true;
        
        // Lighting
        const ambientLight = new THREE.AmbientLight(0xffffff, 0.6);
        this.scene.add(ambientLight);
        
        const directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
        directionalLight.position.set(50, 50, 100);
        directionalLight.castShadow = true;
        directionalLight.shadow.mapSize.width = 2048;
        directionalLight.shadow.mapSize.height = 2048;
        this.scene.add(directionalLight);
        
        // Handle window resize
        window.addEventListener('resize', () => this.onWindowResize());
    }
    
    loadSTL() {
        if (!this.options.stlFile) {
            console.warn('No STL file specified');
            return;
        }
        
        const loader = new THREE.STLLoader();
        loader.load(
            this.options.stlFile,
            (geometry) => {
                this.onSTLLoaded(geometry);
            },
            (progress) => {
                console.log('Loading progress:', progress);
            },
            (error) => {
                console.error('Error loading STL:', error);
            }
        );
    }
    
    onSTLLoaded(geometry) {
        // Compute bounding box and center the geometry
        geometry.computeBoundingBox();
        geometry.computeVertexNormals();
        
        const center = new THREE.Vector3();
        geometry.boundingBox.getCenter(center);
        geometry.translate(-center.x, -center.y, -center.z);
        
        // Create material
        let material;
        if (this.options.defaultTexture) {
            const textureLoader = new THREE.TextureLoader();
            const texture = textureLoader.load(this.options.defaultTexture);
            texture.wrapS = THREE.RepeatWrapping;
            texture.wrapT = THREE.RepeatWrapping;
            material = new THREE.MeshPhongMaterial({ 
                map: texture,
                shininess: 30
            });
        } else {
            material = new THREE.MeshPhongMaterial({ 
                color: 0x00aaff,
                shininess: 30
            });
        }
        
        // Create mesh
        this.stlMesh = new THREE.Mesh(geometry, material);
        this.stlMesh.castShadow = true;
        this.stlMesh.receiveShadow = true;
        this.scene.add(this.stlMesh);
        
        // Adjust camera position
        const size = new THREE.Vector3();
        geometry.boundingBox.getSize(size);
        const maxDim = Math.max(size.x, size.y, size.z);
        this.camera.position.set(0, 0, maxDim * 1.5);
        this.controls.target.set(0, 0, 0);
        this.controls.update();
        
        // Trigger loaded event
        this.onModelLoaded();
    }
    
    onModelLoaded() {
        // Enable customization interface
        document.dispatchEvent(new CustomEvent('kustomizerModelLoaded', {
            detail: { kustomizer: this }
        }));
    }
    
    applyTexture(textureFile) {
        if (!this.stlMesh || !textureFile) return;
        
        const textureLoader = new THREE.TextureLoader();
        const reader = new FileReader();
        
        reader.onload = (e) => {
            const texture = textureLoader.load(e.target.result);
            texture.wrapS = THREE.RepeatWrapping;
            texture.wrapT = THREE.RepeatWrapping;
            
            this.stlMesh.material.map = texture;
            this.stlMesh.material.needsUpdate = true;
            
            this.customization.texture = e.target.result;
        };
        
        reader.readAsDataURL(textureFile);
    }
    
    addText(text, options = {}) {
        const defaultOptions = {
            font: 'Arial',
            size: 10,
            height: 1,
            color: 0x000000,
            position: { x: 0, y: 0, z: 10 },
            rotation: { x: 0, y: 0, z: 0 }
        };
        
        const textOptions = { ...defaultOptions, ...options };
        
        // Load font and create text geometry
        const loader = new THREE.FontLoader();
        loader.load('/wp-content/plugins/kustomizer/assets/fonts/helvetiker_regular.typeface.json', (font) => {
            const textGeometry = new THREE.TextGeometry(text, {
                font: font,
                size: textOptions.size,
                height: textOptions.height,
                curveSegments: 12,
                bevelEnabled: false
            });
            
            textGeometry.computeBoundingBox();
            const centerOffsetX = -0.5 * (textGeometry.boundingBox.max.x - textGeometry.boundingBox.min.x);
            
            const textMaterial = new THREE.MeshPhongMaterial({ color: textOptions.color });
            const textMesh = new THREE.Mesh(textGeometry, textMaterial);
            
            textMesh.position.set(
                textOptions.position.x + centerOffsetX,
                textOptions.position.y,
                textOptions.position.z
            );
            
            textMesh.rotation.set(
                textOptions.rotation.x,
                textOptions.rotation.y,
                textOptions.rotation.z
            );
            
            textMesh.userData = {
                type: 'text',
                text: text,
                options: textOptions
            };
            
            this.scene.add(textMesh);
            this.textMeshes.push(textMesh);
            
            // Add to customization data
            this.customization.textElements.push({
                text: text,
                options: textOptions
            });
        });
    }
    
    addSVG(svgFile, options = {}) {
        const defaultOptions = {
            scale: 1,
            position: { x: 0, y: 0, z: 5 },
            rotation: { x: 0, y: 0, z: 0 },
            color: 0x000000,
            extrude: 1
        };
        
        const svgOptions = { ...defaultOptions, ...options };
        
        const reader = new FileReader();
        reader.onload = (e) => {
            const svgData = e.target.result;
            
            // Parse SVG and create geometry
            const loader = new THREE.SVGLoader();
            const svgDoc = loader.parse(svgData);
            
            const svgGroup = new THREE.Group();
            
            svgDoc.paths.forEach((path) => {
                const shapes = path.toShapes(true);
                
                shapes.forEach((shape) => {
                    const geometry = new THREE.ExtrudeGeometry(shape, {
                        depth: svgOptions.extrude,
                        bevelEnabled: false
                    });
                    
                    const material = new THREE.MeshPhongMaterial({ color: svgOptions.color });
                    const mesh = new THREE.Mesh(geometry, material);
                    svgGroup.add(mesh);
                });
            });
            
            svgGroup.scale.multiplyScalar(svgOptions.scale);
            svgGroup.position.set(
                svgOptions.position.x,
                svgOptions.position.y,
                svgOptions.position.z
            );
            svgGroup.rotation.set(
                svgOptions.rotation.x,
                svgOptions.rotation.y,
                svgOptions.rotation.z
            );
            
            svgGroup.userData = {
                type: 'svg',
                options: svgOptions
            };
            
            this.scene.add(svgGroup);
            this.svgMeshes.push(svgGroup);
            
            // Add to customization data
            this.customization.svgElements.push({
                data: svgData,
                options: svgOptions
            });
        };
        
        reader.readAsText(svgFile);
    }
    
    removeText(index) {
        if (index >= 0 && index < this.textMeshes.length) {
            this.scene.remove(this.textMeshes[index]);
            this.textMeshes.splice(index, 1);
            this.customization.textElements.splice(index, 1);
        }
    }
    
    removeSVG(index) {
        if (index >= 0 && index < this.svgMeshes.length) {
            this.scene.remove(this.svgMeshes[index]);
            this.svgMeshes.splice(index, 1);
            this.customization.svgElements.splice(index, 1);
        }
    }
    
    generateLayout() {
        // Capture the current 3D scene as an image
        this.renderer.render(this.scene, this.camera);
        const dataURL = this.renderer.domElement.toDataURL('image/png');
        
        this.customization.layout = {
            image: dataURL,
            timestamp: new Date().toISOString(),
            data: {
                texture: this.customization.texture,
                textElements: this.customization.textElements,
                svgElements: this.customization.svgElements
            }
        };
        
        return this.customization.layout;
    }
    
    getCustomizationData() {
        return this.customization;
    }
    
    onWindowResize() {
        const containerRect = this.container.getBoundingClientRect();
        this.camera.aspect = containerRect.width / containerRect.height;
        this.camera.updateProjectionMatrix();
        this.renderer.setSize(containerRect.width, containerRect.height);
    }
    
    animate() {
        requestAnimationFrame(() => this.animate());
        this.controls.update();
        this.renderer.render(this.scene, this.camera);
    }
    
    dispose() {
        if (this.renderer) {
            this.renderer.dispose();
        }
        if (this.controls) {
            this.controls.dispose();
        }
        window.removeEventListener('resize', () => this.onWindowResize());
    }
}

// Export for global use
window.Kustomizer = Kustomizer;