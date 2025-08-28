<script setup>
import { ref, onMounted, onUnmounted, watch, computed } from 'vue';
import { router } from '@inertiajs/vue3';

const video = ref(null);
const canvas = ref(null);
const previewOverlay = ref(null);

const stream = ref(null);
const photo = ref(null);
const isSaving = ref(false);

const capturedPhotos = ref([]);
const photoCountForLayout = computed(() => {
    const layout = layouts.find(l => l.value === selectedLayout.value);
    if (layout && layout.value !== 'single') {
        return parseInt(layout.grid.split('x')[0]) * parseInt(layout.grid.split('x')[1]);
    }
    return 1;
});
const currentPhotoIndex = computed(() => capturedPhotos.value.length + 1);
const isMultiPhotoLayout = computed(() => photoCountForLayout.value > 1);

const effects = [
    { name: 'Normal', value: 'none' },
    { name: 'Hitam Putih', value: 'grayscale(100%)' },
    { name: 'Sepia Vintage', value: 'sepia(100%)' },
    { name: 'Cyberpunk', value: 'contrast(150%) hue-rotate(270deg) saturate(200%)' },
    { name: 'Sunset', value: 'sepia(50%) hue-rotate(320deg) saturate(150%)' },
    { name: 'Dreamy', value: 'blur(1px) brightness(120%) saturate(130%)' }
];

const layouts = [
    { name: 'Single Photo', value: 'single', grid: '1x1' },
    { name: 'Photo Strip (4)', value: 'strip4', grid: '4x1' },
    { name: 'Kotak (4)', value: 'square4', grid: '2x2' }
];

const overlays = [
    { name: 'Tidak Ada', value: 'none' },
    { name: 'Frame Bunga 🌸', value: 'flower-frame' },
    { name: 'Frame Love 💕', value: 'love-frame' },
    { name: 'Stiker Lucu ✨', value: 'cute-stickers' },
    { name: 'Topi Party 🎩', value: 'party-hat' },
    { name: 'Kacamata Keren 🕶️', value: 'cool-glasses' },
    { name: 'Mahkota 👑', value: 'crown' },
    { name: 'Telinga Kucing 😺', value: 'cat-ears' },
    { name: 'Confetti 🎊', value: 'confetti' },
    { name: 'Teks Bucin 😍', value: 'bucin' },
    { name: 'K-Pop Vibes 💜', value: 'kpop' },
    { name: 'Angel Wings 👼', value: 'angel' },
    { name: 'Devil Mode 😈', value: 'devil' }
];

const selectedEffect = ref('none');
const selectedOverlay = ref('none');
const selectedLayout = ref('single');

const startCamera = async () => {
    try {
        stream.value = await navigator.mediaDevices.getUserMedia({
            video: { width: 1280, height: 720, facingMode: 'user' },
            audio: false,
        });
        if (video.value) {
            video.value.srcObject = stream.value;
        }
    } catch (error) {
        console.error("Kesalahan saat mengakses kamera:", error);
        alert("Tidak dapat mengakses kamera. Pastikan Anda memberikan izin.");
    }
};

const stopCamera = () => {
    if (stream.value) {
        stream.value.getTracks().forEach(track => track.stop());
    }
};

const drawOverlayOnCanvas = (context, width, height, overlayType) => {
    context.save();
    context.textAlign = 'center';
    context.textBaseline = 'middle';
    context.translate(width, 0);
    context.scale(-1, 1);

    const baseEmojiSize = width * 0.08;

    switch (overlayType) {
        case 'party-hat':
            context.font = `${baseEmojiSize * 2}px Arial`;
            context.fillText('🎩', width * 0.5, height * 0.20);
            break;
        case 'cool-glasses':
            context.font = `${baseEmojiSize * 2.5}px Arial`;
            context.fillText('🕶️', width * 0.5, height * 0.45);
            break;
        case 'crown':
            context.font = `${baseEmojiSize * 2}px Arial`;
            context.fillText('👑', width * 0.5, height * 0.15);
            break;
        case 'cat-ears':
            context.font = `${baseEmojiSize * 2}px Arial`;
            context.fillText('😺', width * 0.5, height * 0.20);
            break;
        case 'flower-frame':
            context.font = `${baseEmojiSize * 1.2}px Arial`;
            context.fillText('🌸', width * 0.9, height * 0.1);
            context.fillText('🌺', width * 0.1, height * 0.1);
            context.fillText('🌸', width * 0.9, height * 0.9);
            context.fillText('🌺', width * 0.1, height * 0.9);
            break;
        case 'love-frame':
        case 'cute-stickers':
        case 'confetti':
        case 'bucin':
        case 'kpop':
        case 'angel':
        case 'devil':
            const items = {
                'love-frame': ['💕', '💖', '❤️'],
                'cute-stickers': ['✨', '🌟', '🦄', '🎀'],
                'confetti': ['🎉', '🎊', '🥳', '🎈'],
                'bucin': ['😍', '🥰', '😘', '🫶'],
                'kpop': ['💜', '🫰', '✌️', '✨'],
                'angel': ['👼', '✨', '😇'],
                'devil': ['😈', '🔥', '👹']
            };
            context.font = `${baseEmojiSize}px Arial`;
            for (let i = 0; i < 20; i++) {
                const x = Math.random() * width;
                const y = Math.random() * height;
                const emoji = items[overlayType][Math.floor(Math.random() * items[overlayType].length)];
                context.fillText(emoji, x, y);
            }
            if (overlayType === 'bucin') {
                context.font = `bold ${baseEmojiSize * 0.9}px 'Comic Sans MS'`;
                context.fillStyle = '#FF69B4';
                context.save();
                context.scale(-1, 1);
                context.fillText('I ❤️ U', -width / 2, height * 0.9);
                context.restore();
            }
            break;
    }
    context.restore();
};

const captureSinglePhotoFrame = () => {
    const context = canvas.value.getContext('2d');
    const videoEl = video.value;

    canvas.value.width = videoEl.videoWidth;
    canvas.value.height = videoEl.videoHeight;

    context.save();
    context.translate(videoEl.videoWidth, 0);
    context.scale(-1, 1);
    context.filter = effects.find(e => e.value === selectedEffect.value)?.value || 'none';
    context.drawImage(videoEl, 0, 0, videoEl.videoWidth, videoEl.videoHeight);
    context.restore();

    context.filter = 'none';
    if (selectedOverlay.value !== 'none') {
        drawOverlayOnCanvas(context, videoEl.videoWidth, videoEl.videoHeight, selectedOverlay.value);
    }

    return canvas.value.toDataURL('image/png');
};

const takePhoto = () => {
    if (isMultiPhotoLayout.value) {
        if (capturedPhotos.value.length < photoCountForLayout.value) {
            const currentFrame = captureSinglePhotoFrame();
            capturedPhotos.value.push(currentFrame);

            if (capturedPhotos.value.length === photoCountForLayout.value) {
                combineCapturedPhotos();
            }
        }
    } else {
        photo.value = captureSinglePhotoFrame();
    }
};

const combineCapturedPhotos = () => {
    if (capturedPhotos.value.length === 0) return;

    const layout = selectedLayout.value;
    const finalCanvas = document.createElement('canvas');
    const ctx = finalCanvas.getContext('2d');

    const firstImg = new Image();
    firstImg.onload = () => {
        const singlePhotoWidth = firstImg.width;
        const singlePhotoHeight = firstImg.height;

        let finalWidth = 0;
        let finalHeight = 0;
        const padding = 15;

        switch (layout) {
            case 'strip4':
                finalWidth = singlePhotoWidth + (padding * 2);
                finalHeight = (singlePhotoHeight * 4) + (padding * 5);
                finalCanvas.width = finalWidth;
                finalCanvas.height = finalHeight;
                ctx.fillStyle = 'white';
                ctx.fillRect(0, 0, finalWidth, finalHeight);

                let loadedCountStrip = 0;
                capturedPhotos.value.forEach((imgData, index) => {
                    const tempImg = new Image();
                    tempImg.onload = () => {
                        const x = padding;
                        const y = (singlePhotoHeight * index) + (padding * (index + 1));
                        ctx.drawImage(tempImg, x, y, singlePhotoWidth, singlePhotoHeight);
                        loadedCountStrip++;
                        if (loadedCountStrip === capturedPhotos.value.length) {
                            photo.value = finalCanvas.toDataURL('image/png');
                        }
                    };
                    tempImg.src = imgData;
                });
                break;

            case 'square4':
                finalWidth = (singlePhotoWidth * 2) + (padding * 3);
                finalHeight = (singlePhotoHeight * 2) + (padding * 3);
                finalCanvas.width = finalWidth;
                finalCanvas.height = finalHeight;
                ctx.fillStyle = 'white';
                ctx.fillRect(0, 0, finalWidth, finalHeight);

                let loadedCountSquare = 0;
                capturedPhotos.value.forEach((imgData, index) => {
                    const tempImg = new Image();
                    tempImg.onload = () => {
                        const row = Math.floor(index / 2);
                        const col = index % 2;
                        const x = (singlePhotoWidth * col) + (padding * (col + 1));
                        const y = (singlePhotoHeight * row) + (padding * (row + 1));
                        ctx.drawImage(tempImg, x, y, singlePhotoWidth, singlePhotoHeight);
                        loadedCountSquare++;
                        if (loadedCountSquare === capturedPhotos.value.length) {
                            photo.value = finalCanvas.toDataURL('image/png');
                        }
                    };
                    tempImg.src = imgData;
                });
                break;

            default:
                photo.value = capturedPhotos.value[0];
                break;
        }
    };
    if (capturedPhotos.value.length > 0) {
        firstImg.src = capturedPhotos.value[0];
    }
};

const updatePreviewOverlay = () => {
    if (!previewOverlay.value) return;
    const overlay = previewOverlay.value;
    overlay.innerHTML = '';

    if (selectedOverlay.value === 'none') return;

    const createItem = (emoji, className, style = {}) => {
        const el = document.createElement('div');
        el.className = `preview-overlay-item ${className}`;
        el.innerHTML = emoji;
        Object.assign(el.style, style);
        overlay.appendChild(el);
    };

    switch (selectedOverlay.value) {
        case 'party-hat': createItem('🎩', 'hat'); break;
        case 'cool-glasses': createItem('🕶️', 'glasses'); break;
        case 'crown': createItem('👑', 'crown'); break;
        case 'cat-ears': createItem('😺', 'cat-ears'); break;
        case 'flower-frame':
            createItem('🌸', 'corner top-left');
            createItem('🌺', 'corner top-right');
            createItem('🌸', 'corner bottom-left');
            createItem('🌺', 'corner bottom-right');
            break;
        case 'love-frame':
        case 'cute-stickers':
        case 'confetti':
        case 'bucin':
        case 'kpop':
        case 'angel':
        case 'devil':
            const items = {
                'love-frame': ['💕', '💖'], 'cute-stickers': ['✨', '🌟', '🦄'], 'confetti': ['🎉', '🎊'],
                'bucin': ['😍', '🥰', '😘'], 'kpop': ['💜', '🫰', '✌️'], 'angel': ['👼', '✨', '😇'], 'devil': ['😈', '🔥']
            };
            for (let i = 0; i < 10; i++) {
                createItem(items[selectedOverlay.value][Math.floor(Math.random() * items[selectedOverlay.value].length)], 'floating', {
                    left: `${Math.random() * 90}%`,
                    top: `${Math.random() * 90}%`,
                    animationDelay: `${Math.random() * 3}s`,
                    fontSize: `${Math.random() * 1.5 + 1.5}rem`
                });
            }
            if (selectedOverlay.value === 'bucin') {
                createItem('I ❤️ U', 'text-overlay bottom-center');
            }
            break;
    }
};

watch(selectedOverlay, updatePreviewOverlay);

const downloadPhoto = () => {
    if (!photo.value) return;
    const link = document.createElement('a');
    link.href = photo.value;
    link.download = `photobooth-${Date.now()}.png`;
    link.click();
};

const uploadPhoto = () => {
    if (!photo.value) return;
    isSaving.value = true;
    router.post('/photobooth/store', { image: photo.value }, {
        onSuccess: () => {
            alert('Foto berhasil diunggah! 🎉');
            retakePhoto();
        },
        onError: (errors) => {
            console.error("Upload error:", errors);
            alert('Gagal mengunggah foto. Silakan coba lagi.');
        },
        onFinish: () => { isSaving.value = false; }
    });
};

const retakePhoto = () => {
    photo.value = null;
    capturedPhotos.value = [];
};

onMounted(() => {
    startCamera();
    updatePreviewOverlay();
});

onUnmounted(stopCamera);
</script>

<template>
    <div class="photobooth-container">
        <h1 class="title">📸 Photobooth Nanskuyy 🎪</h1>
        <div class="main-grid">
            <div class="camera-section">
                <div class="camera-box">
                    <video ref="video" autoplay playsinline :style="{ filter: selectedEffect }"
                        class="video-feed"></video>
                    <div ref="previewOverlay" class="preview-overlay"></div>
                </div>
                <canvas ref="canvas" style="display: none;"></canvas>
                <div class="controls-panel">
                    <div class="control-group">
                        <label for="effect-select">🎨 Filter Efek:</label>
                        <select id="effect-select" v-model="selectedEffect" class="control-select">
                            <option v-for="effect in effects" :key="effect.value" :value="effect.value">{{ effect.name
                                }}</option>
                        </select>
                    </div>
                    <div class="control-group">
                        <label for="overlay-select">🎭 Dekorasi:</label>
                        <select id="overlay-select" v-model="selectedOverlay" class="control-select">
                            <option v-for="overlay in overlays" :key="overlay.value" :value="overlay.value">{{
                                overlay.name }}</option>
                        </select>
                    </div>
                    <div class="control-group">
                        <label for="layout-select">📐 Layout Foto:</label>
                        <select id="layout-select" v-model="selectedLayout" class="control-select"
                            :disabled="capturedPhotos.length > 0">
                            <option v-for="layout in layouts" :key="layout.value" :value="layout.value">{{ layout.name
                                }} ({{ layout.grid }})</option>
                        </select>
                        <p v-if="capturedPhotos.length > 0" class="layout-locked-msg">
                            Layout terkunci setelah mengambil foto pertama.
                        </p>
                    </div>
                    <button @click="takePhoto" class="snap-button" :disabled="photo !== null && !isMultiPhotoLayout">
                        <span v-if="isMultiPhotoLayout && capturedPhotos.length < photoCountForLayout">
                            📸 Ambil Foto {{ currentPhotoIndex }}/{{ photoCountForLayout }}!
                        </span>
                        <span v-else-if="photo !== null">
                            ✅ Foto Sudah Diambil
                        </span>
                        <span v-else>
                            📸 Ambil Foto!
                        </span>
                    </button>
                    <p v-if="isMultiPhotoLayout && capturedPhotos.length > 0 && capturedPhotos.length < photoCountForLayout"
                        class="snap-instruction">
                        Tekan lagi untuk foto berikutnya!
                    </p>
                </div>
            </div>
            <div class="preview-section">
                <div v-if="photo" class="photo-preview">
                    <img :src="photo" alt="Hasil Foto" class="preview-image">
                    <div class="photo-actions">
                        <button @click="retakePhoto" class="action-btn retake-btn">🔄 Foto Lagi</button>
                        <button @click="downloadPhoto" class="action-btn download-btn">💾 Unduh</button>
                        <button @click="uploadPhoto" :disabled="isSaving" class="action-btn upload-btn">
                            {{ isSaving ? '⏳ Menyimpan...' : '☁️ Simpan' }}
                        </button>
                    </div>
                </div>
                <div v-else class="placeholder">
                    <div class="placeholder-icon">📷</div>
                    <p>
                        <span v-if="isMultiPhotoLayout && capturedPhotos.length > 0">
                            Mengambil Foto {{ capturedPhotos.length }}/{{ photoCountForLayout }}...
                        </span>
                        <span v-else>
                            Hasil fotomu akan tampil di sini!
                        </span>
                    </p>
                    <p class="placeholder-tip">Pilih gaya favoritmu! ✨</p>
                    <div v-if="isMultiPhotoLayout && capturedPhotos.length > 0" class="mini-previews">
                        <img v-for="(p, i) in capturedPhotos" :key="i" :src="p" class="mini-preview-thumb"
                            :class="{ 'active': i === currentPhotoIndex - 2 }">
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
:root {
    --primary-color: #667eea;
    --secondary-color: #764ba2;
    --accent-color: #FFD700;
    --snap-color-1: #FF6B6B;
    --snap-color-2: #FF8E53;
    --text-light: #ffffff;
    --text-dark: #333333;
}

.photobooth-container {
    max-width: 1600px;
    margin: 1rem auto;
    padding: clamp(1rem, 3vw, 2rem);
    font-family: 'Comic Sans MS', cursive, sans-serif;
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
    min-height: 100vh;
    border-radius: 20px;
    color: var(--text-light);
    text-align: center;
}

.title {
    font-size: clamp(2rem, 5vw, 2.5rem);
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
    margin-bottom: 2rem;
    animation: title-bounce 2s infinite;
}

.main-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: clamp(1.5rem, 4vw, 3rem);
}

.camera-section {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.camera-box {
    position: relative;
    border: 4px solid var(--accent-color);
    border-radius: 20px;
    background: linear-gradient(45deg, var(--snap-color-1), var(--snap-color-2));
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    overflow: hidden;
    aspect-ratio: 16 / 9;
    width: 100%;
    max-width: 700px;
}

.video-feed {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 15px;
    transform: scaleX(-1);
}

.preview-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    pointer-events: none;
    z-index: 10;
    transform: scaleX(-1);
}

.preview-overlay-item {
    position: absolute;
    animation: float 3s ease-in-out infinite;
    text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.4);
    pointer-events: none;
    user-select: none;
}

.hat {
    top: 20%;
    left: 50%;
    transform: translate(-50%, -50%) scaleX(-1);
    font-size: clamp(3rem, 10vw, 8rem);
}

.glasses {
    top: 45%;
    left: 50%;
    transform: translate(-50%, -50%) scaleX(-1);
    font-size: clamp(3rem, 12vw, 9rem);
}

.crown {
    top: 15%;
    left: 50%;
    transform: translate(-50%, -50%) scaleX(-1);
    font-size: clamp(3rem, 11vw, 8rem);
}

.cat-ears {
    top: 20%;
    left: 50%;
    transform: translate(-50%, -50%) scaleX(-1);
    font-size: clamp(3rem, 11vw, 8rem);
}

.corner {
    font-size: clamp(2rem, 5vw, 3rem);
    transform: scaleX(-1);
}

.top-left {
    top: 5%;
    left: 5%;
}

.top-right {
    top: 5%;
    right: 5%;
}

.bottom-left {
    bottom: 5%;
    left: 5%;
}

.bottom-right {
    bottom: 5%;
    right: 5%;
}

.text-overlay {
    bottom: 10%;
    left: 50%;
    transform: translateX(-50%) scaleX(-1);
    font-size: clamp(1.5rem, 4vw, 2.5rem);
    font-weight: bold;
    color: #FF69B4;
}

.controls-panel {
    margin-top: 1.5rem;
    padding: clamp(1rem, 3vw, 1.5rem);
    background: rgba(255, 255, 255, 0.9);
    border-radius: 15px;
    backdrop-filter: blur(10px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
    color: var(--text-dark);
    width: 100%;
    max-width: 700px;
    text-align: left;
}

.control-group {
    margin-bottom: 1rem;
}

.control-group label {
    display: block;
    font-weight: bold;
    margin-bottom: 0.5rem;
    font-size: 1.1rem;
}

.control-select {
    width: 100%;
    padding: 0.8rem;
    border: 2px solid #ddd;
    border-radius: 10px;
    font-size: 1rem;
    background: white;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23666' width='18px' height='18px'%3E%3Cpath d='M7 10l5 5 5-5z'/%3E%3Cpath d='M0 0h24v24H0z' fill='none'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.8rem center;
    background-size: 18px;
}

.control-select:disabled {
    background-color: #e0e0e0;
    cursor: not-allowed;
}

.layout-locked-msg {
    font-size: 0.9rem;
    color: #666;
    margin-top: 0.5rem;
    text-align: center;
}

.snap-button {
    width: 100%;
    padding: 1rem;
    border: none;
    border-radius: 15px;
    font-size: 1.3rem;
    font-weight: bold;
    background: linear-gradient(45deg, var(--snap-color-1), var(--snap-color-2));
    color: white;
    cursor: pointer;
    transition: all 0.3s ease;
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
    box-shadow: 0 5px 15px rgba(255, 107, 107, 0.4);
    margin-top: 1rem;
}

.snap-button:hover:not(:disabled) {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(255, 107, 107, 0.6);
}

.snap-button:disabled {
    background: #999;
    cursor: not-allowed;
    box-shadow: none;
}

.snap-instruction {
    font-size: 1rem;
    color: var(--text-light);
    margin-top: 0.5rem;
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
}

.preview-section {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border: 4px solid var(--accent-color);
    border-radius: 20px;
    padding: clamp(1rem, 3vw, 1.5rem);
    background: linear-gradient(45deg, #4ECDC4, #44A08D);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    min-height: 500px;
    flex-grow: 1;
}

.photo-preview {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 100%;
}

.preview-image {
    width: 100%;
    height: auto;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
    object-fit: contain;
    max-height: 80vh;
}

.photo-actions {
    display: flex;
    gap: 0.8rem;
    margin-top: 1.5rem;
    width: 100%;
    flex-wrap: wrap;
}

.action-btn {
    flex: 1;
    min-width: 120px;
    padding: 0.8rem;
    border: none;
    border-radius: 10px;
    font-size: 1rem;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s ease;
}

.retake-btn {
    background: linear-gradient(45deg, #FFA726, #FB8C00);
    color: white;
}

.download-btn {
    background: linear-gradient(45deg, #66BB6A, #4CAF50);
    color: white;
}

.upload-btn {
    background: linear-gradient(45deg, #42A5F5, #2196F3);
    color: white;
}

.action-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
}

.upload-btn:disabled {
    background: #999;
    cursor: not-allowed;
}

.placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    border: 3px dashed rgba(255, 255, 255, 0.6);
    border-radius: 15px;
    background: rgba(255, 255, 255, 0.1);
    padding: 1rem;
}

.placeholder-icon {
    font-size: 4rem;
    animation: pulse 2s infinite;
}

.placeholder p {
    font-size: 1.2rem;
    margin: 0.5rem 0;
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
}

.placeholder-tip {
    font-size: 1rem !important;
    opacity: 0.8;
}

.mini-previews {
    display: flex;
    gap: 10px;
    margin-top: 1rem;
    flex-wrap: wrap;
    justify-content: center;
}

.mini-preview-thumb {
    width: 80px;
    height: 45px;
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid transparent;
    transition: all 0.2s ease;
}

.mini-preview-thumb.active {
    border-color: var(--accent-color);
    transform: scale(1.1);
}

@keyframes title-bounce {

    0%,
    20%,
    50%,
    80%,
    100% {
        transform: translateY(0);
    }

    40% {
        transform: translateY(-10px);
    }

    60% {
        transform: translateY(-5px);
    }
}

@keyframes float {

    0%,
    100% {
        transform: translateY(0px) scale(1);
    }

    50% {
        transform: translateY(-10px) scale(1.05);
    }
}

@keyframes pulse {

    0%,
    100% {
        transform: scale(1);
    }

    50% {
        transform: scale(1.1);
    }
}

@media (max-width: 900px) {
    .main-grid {
        grid-template-columns: 1fr;
    }

    .preview-section {
        min-height: 400px;
    }

    .photo-actions {
        flex-direction: column;
        gap: 0.5rem;
    }

    .action-btn {
        width: 100%;
    }

    .camera-box,
    .controls-panel {
        max-width: none;
    }
}
</style>