import Alpine from "alpinejs";
import { registerBlatUI } from "./blatui-core.js";
import productPhotoPicker from "./product-photo-picker.js";

window.Alpine = Alpine;

registerBlatUI(Alpine);
Alpine.data("productPhotoPicker", productPhotoPicker);
Alpine.start();
