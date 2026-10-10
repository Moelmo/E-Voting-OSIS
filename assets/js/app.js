document.addEventListener('DOMContentLoaded', () => {
	const panel = document.querySelector('#scanner-panel');
	const form = document.querySelector('#login-form');
	const manualToggle = document.querySelector('#manual-toggle');
	const nisInput = document.querySelector('#nis');
	const status = document.querySelector('#scanner-status');

	if (!panel || !form || !manualToggle || !nisInput || !status) {
		return;
	}

	let scanner = null;
	let scannerRunning = false;
	let nativeStream = null;
	let nativeScanTimer = null;

	const stopScanner = async () => {
		if (scanner && scannerRunning) {
			await scanner.stop();
			scanner.clear();
			scannerRunning = false;
		}

		if (nativeScanTimer) {
			clearTimeout(nativeScanTimer);
			nativeScanTimer = null;
		}

		if (nativeStream) {
			nativeStream.getTracks().forEach((track) => track.stop());
			nativeStream = null;
		}
	};

	const onScanSuccess = async (decodedText) => {
		const value = decodedText.trim();

		if (!value) {
			return;
		}

		nisInput.value = value;
		status.textContent = `Barcode terbaca: ${value}. Mengirim...`;
		await stopScanner();
		form.submit();
	};

	const startNativeScanner = async () => {
		if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
			throw new Error('Browser tidak mendukung akses kamera.');
		}

		const video = document.createElement('video');
		video.autoplay = true;
		video.muted = true;
		video.playsInline = true;
		video.setAttribute('aria-label', 'Tampilan kamera untuk pemindai barcode');
		const reader = document.querySelector('#qr-reader');
		reader.replaceChildren(video);

		nativeStream = await navigator.mediaDevices.getUserMedia({
			video: { facingMode: { ideal: 'environment' } },
			audio: false,
		});
		video.srcObject = nativeStream;
		await video.play();
		scannerRunning = true;
		status.textContent = 'Kamera aktif. Arahkan barcode NIS ke kamera.';

		if (typeof BarcodeDetector === 'undefined') {
			status.textContent = 'Kamera aktif. Pemindaian otomatis tidak didukung browser ini; gunakan input NIS manual.';
			return;
		}

		const detector = new BarcodeDetector();
		const scanFrame = async () => {
			if (!nativeStream) {
				return;
			}

			if (video.readyState < HTMLMediaElement.HAVE_ENOUGH_DATA) {
				nativeScanTimer = setTimeout(scanFrame, 250);
				return;
			}

			try {
				const barcodes = await detector.detect(video);
				if (barcodes.length && barcodes[0].rawValue) {
					await onScanSuccess(barcodes[0].rawValue);
					return;
				}
			} catch (error) {
				status.textContent = 'Kamera aktif. Arahkan barcode atau gunakan input NIS manual.';
			}

			nativeScanTimer = setTimeout(scanFrame, 250);
		};

		scanFrame();
	};

	const startScanner = async () => {
		if (typeof Html5Qrcode === 'undefined') {
			status.textContent = 'Menyiapkan kamera browser...';
			try {
				await startNativeScanner();
			} catch (error) {
				status.textContent = 'Kamera tidak tersedia. Izinkan akses kamera atau gunakan input NIS manual.';
			}
			return;
		}

		status.textContent = 'Mendeteksi kamera laptop atau webcam...';

		try {
			const cameras = await Promise.race([
				Html5Qrcode.getCameras(),
				new Promise((resolve, reject) => {
					setTimeout(() => reject(new Error('Deteksi kamera melewati batas waktu.')), 5000);
				}),
			]);

			if (!cameras.length) {
				throw new Error('Tidak ada kamera yang terdeteksi.');
			}

			const orderedCameras = [...cameras].sort((firstCamera, secondCamera) => {
				const firstLabel = (firstCamera.label || '').toLowerCase();
				const secondLabel = (secondCamera.label || '').toLowerCase();
				const firstPreferred = /back|rear|environment|belakang/.test(firstLabel);
				const secondPreferred = /back|rear|environment|belakang/.test(secondLabel);
				return Number(secondPreferred) - Number(firstPreferred);
			});

			const scanConfig = {
				fps: 10,
				qrbox: (viewfinderWidth, viewfinderHeight) => ({
					width: Math.min(viewfinderWidth * 0.82, 620),
					height: Math.min(viewfinderHeight * 0.62, 360),
				}),
				aspectRatio: 1.777778,
			};

			for (const camera of orderedCameras) {
				const candidateScanner = new Html5Qrcode('qr-reader');
				scanner = candidateScanner;

				try {
					await candidateScanner.start(camera.id, scanConfig, onScanSuccess, () => {});
					scannerRunning = true;
					status.textContent = `Kamera aktif: ${camera.label || 'kamera tersedia'}. Arahkan ke barcode NIS.`;
					break;
				} catch (cameraError) {
					try {
						candidateScanner.clear();
					} catch {}

					if (camera === orderedCameras[orderedCameras.length - 1]) {
						throw cameraError;
					}
				}
			}
		} catch (error) {
			try {
				await startNativeScanner();
			} catch (fallbackError) {
				status.textContent = 'Kamera tidak dapat diakses. Izinkan kamera browser atau gunakan input NIS manual.';
			}
		}
	};

	manualToggle.addEventListener('click', () => {
		const shouldOpen = form.hidden;
		form.hidden = !shouldOpen;
		manualToggle.setAttribute('aria-expanded', String(shouldOpen));

		if (shouldOpen) {
			nisInput.focus();
		}
	});

	startScanner();
});
