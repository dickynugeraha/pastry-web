export default function productPhotoPicker(existingPhotos = [], removedIds = [], selectedPrimaryId = null) {
    return {
        existingPhotos,
        removedIds: removedIds.map(Number),
        primaryId: selectedPrimaryId ? Number(selectedPrimaryId) : null,
        newPhotos: [],
        nextId: 1,
        dragging: false,
        error: "",
        previewUrl: null,
        previewName: "",

        get visibleExisting() {
            return this.existingPhotos.filter((photo) => !this.removedIds.includes(photo.id));
        },

        get totalPhotos() {
            return this.visibleExisting.length + this.newPhotos.length;
        },

        addFiles(fileList) {
            this.error = "";

            for (const file of Array.from(fileList)) {
                if (this.totalPhotos >= 5) {
                    this.error = "Maksimal 5 foto per produk. Hapus foto lain terlebih dahulu.";
                    break;
                }

                if (!["image/jpeg", "image/png", "image/webp"].includes(file.type)) {
                    this.error = "Gunakan file JPG, PNG, atau WebP.";
                    continue;
                }

                if (file.size > 4 * 1024 * 1024) {
                    this.error = "Ukuran setiap foto maksimal 4 MB.";
                    continue;
                }

                this.newPhotos.push({ id: this.nextId++, file, url: URL.createObjectURL(file) });
            }

            this.syncFiles();
        },

        syncFiles() {
            const transfer = new DataTransfer();

            for (const photo of this.newPhotos) {
                transfer.items.add(photo.file);
            }

            this.$refs.input.files = transfer.files;
        },

        drop(event) {
            this.dragging = false;
            this.addFiles(event.dataTransfer.files);
        },

        removeExisting(id) {
            this.removedIds.push(id);

            if (this.primaryId === id) {
                this.primaryId = this.visibleExisting[0]?.id ?? null;
            }
        },

        removeNew(id) {
            const photo = this.newPhotos.find((item) => item.id === id);

            if (photo) {
                URL.revokeObjectURL(photo.url);
            }

            this.newPhotos = this.newPhotos.filter((item) => item.id !== id);
            this.syncFiles();
        },

        openPreview(url, name) {
            this.previewUrl = url;
            this.previewName = name;
        },

        closePreview() {
            this.previewUrl = null;
            this.previewName = "";
        },

        destroy() {
            for (const photo of this.newPhotos) {
                URL.revokeObjectURL(photo.url);
            }
        },
    };
}
