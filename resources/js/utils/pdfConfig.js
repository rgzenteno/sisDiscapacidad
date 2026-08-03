import * as pdfjsLib from "pdfjs-dist";

// Worker copiado como .js normal, sin problemas de MIME type
pdfjsLib.GlobalWorkerOptions.workerSrc = "/vendor/pdf.worker.min.js";

export default pdfjsLib;
