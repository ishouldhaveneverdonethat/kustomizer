/**
 * Simplified Orbit Controls for Three.js
 */
THREE.OrbitControls = function (object, domElement) {
    this.object = object;
    this.domElement = domElement || document;
    this.enabled = true;
    this.target = new THREE.Vector3();
    this.enableDamping = false;
    this.dampingFactor = 0.25;
    this.enableZoom = true;
    this.enableRotate = true;
    this.enablePan = true;

    var scope = this;
    var changeEvent = { type: 'change' };
    var startEvent = { type: 'start' };
    var endEvent = { type: 'end' };

    var STATE = { NONE: -1, ROTATE: 0, DOLLY: 1, PAN: 2 };
    var state = STATE.NONE;

    var spherical = new THREE.Spherical();
    var sphericalDelta = new THREE.Spherical();
    var scale = 1;
    var panOffset = new THREE.Vector3();

    var rotateStart = new THREE.Vector2();
    var rotateEnd = new THREE.Vector2();
    var rotateDelta = new THREE.Vector2();

    this.update = function () {
        var offset = new THREE.Vector3();
        var quat = new THREE.Quaternion().setFromUnitVectors(object.up, new THREE.Vector3(0, 1, 0));
        var quatInverse = quat.clone().invert();

        return function update() {
            var position = scope.object.position;
            offset.copy(position).sub(scope.target);
            offset.applyQuaternion(quat);
            spherical.setFromVector3(offset);

            if (scope.enableDamping) {
                spherical.theta += sphericalDelta.theta * scope.dampingFactor;
                spherical.phi += sphericalDelta.phi * scope.dampingFactor;
            } else {
                spherical.theta += sphericalDelta.theta;
                spherical.phi += sphericalDelta.phi;
            }

            spherical.phi = Math.max(0.000001, Math.min(Math.PI - 0.000001, spherical.phi));
            spherical.radius *= scale;
            spherical.radius = Math.max(0.01, spherical.radius);

            scope.target.add(panOffset);
            offset.setFromSpherical(spherical);
            offset.applyQuaternion(quatInverse);
            position.copy(scope.target).add(offset);
            scope.object.lookAt(scope.target);

            if (scope.enableDamping) {
                sphericalDelta.theta *= (1 - scope.dampingFactor);
                sphericalDelta.phi *= (1 - scope.dampingFactor);
                panOffset.multiplyScalar(1 - scope.dampingFactor);
            } else {
                sphericalDelta.set(0, 0, 0);
                panOffset.set(0, 0, 0);
            }

            scale = 1;
            return true;
        };
    }();

    function onMouseDown(event) {
        if (!scope.enabled) return;
        event.preventDefault();
        
        if (event.button === 0) {
            state = STATE.ROTATE;
            rotateStart.set(event.clientX, event.clientY);
        }
        
        document.addEventListener('mousemove', onMouseMove, false);
        document.addEventListener('mouseup', onMouseUp, false);
        scope.dispatchEvent(startEvent);
    }

    function onMouseMove(event) {
        if (!scope.enabled) return;
        event.preventDefault();

        if (state === STATE.ROTATE) {
            rotateEnd.set(event.clientX, event.clientY);
            rotateDelta.subVectors(rotateEnd, rotateStart);
            
            var element = scope.domElement === document ? scope.domElement.body : scope.domElement;
            var rotateSpeed = 2 * Math.PI / element.clientHeight;
            
            sphericalDelta.theta -= rotateSpeed * rotateDelta.x;
            sphericalDelta.phi -= rotateSpeed * rotateDelta.y;
            
            rotateStart.copy(rotateEnd);
            scope.update();
        }
    }

    function onMouseUp() {
        if (!scope.enabled) return;
        document.removeEventListener('mousemove', onMouseMove, false);
        document.removeEventListener('mouseup', onMouseUp, false);
        scope.dispatchEvent(endEvent);
        state = STATE.NONE;
    }

    function onWheel(event) {
        if (!scope.enabled || !scope.enableZoom) return;
        event.preventDefault();
        event.stopPropagation();
        
        if (event.deltaY < 0) {
            scale /= 0.95;
        } else {
            scale *= 0.95;
        }
        
        scope.update();
        scope.dispatchEvent(startEvent);
        scope.dispatchEvent(endEvent);
    }

    this.domElement.addEventListener('mousedown', onMouseDown, false);
    this.domElement.addEventListener('wheel', onWheel, false);
    this.domElement.addEventListener('contextmenu', function(event) { event.preventDefault(); }, false);

    this.dispose = function () {
        scope.domElement.removeEventListener('mousedown', onMouseDown, false);
        scope.domElement.removeEventListener('wheel', onWheel, false);
    };
};

THREE.OrbitControls.prototype = Object.create(THREE.EventDispatcher.prototype);
THREE.OrbitControls.prototype.constructor = THREE.OrbitControls;