<!doctype html>
<html>
<head>
<meta charset="utf-8" />
<title>View of {{$article->title}}</title>
   <link rel="apple-touch-icon" sizes="180x180" href="{{asset('frontend/images/apple-touch-icon.png')}}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{asset('frontend/images/favicon-32x32.png')}}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{asset('frontend/images/favicon-16x16.png')}}">
    <link rel="manifest" href="{{asset('frontend/images/site.webmanifest')}}">
    <meta name="google-site-verification" content="Vk_tQj-yojCvo9KJ9NrFtuHK9mAOvN60Fh4uOsW8QxQ" />

<style>
  body { margin:0; background:#f0f0f0; }
  #viewer { width:100%; height:100vh; overflow:auto; padding:20px; box-sizing:border-box; }
  canvas { display:block; margin:10px auto; background:white; box-shadow:0 2px 8px rgba(0,0,0,0.15); }

  /* Blur effect for screenshot protection */
  .blurred {
    filter: blur(25px) brightness(0%);
  }
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
</head>

<body>
<div id="viewer"></div>

<script>
  // PDF URL from Laravel
  const pdfUrl = "{{ $pdfPath }}";

  pdfjsLib.GlobalWorkerOptions.workerSrc =
    "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js";

  const viewer = document.getElementById("viewer");

  fetch(pdfUrl)
    .then(res => res.arrayBuffer())
    .then(buffer => pdfjsLib.getDocument({ data: buffer }).promise)
    .then(async pdf => {

      for (let i = 1; i <= pdf.numPages; i++) {
        const page = await pdf.getPage(i);
        const viewport = page.getViewport({ scale: 1.5 });

        const canvas = document.createElement("canvas");
        const ctx = canvas.getContext("2d");

        canvas.width = viewport.width;
        canvas.height = viewport.height;

        const renderContext = {
          canvasContext: ctx,
          viewport: viewport
        };

        viewer.appendChild(canvas);
        await page.render(renderContext).promise;
      }

    })
    .catch(err => console.error("PDF Load error:", err));


  /* ===============================
      SCREENSHOT PROTECTION
     (PrintScreen Blur)
  ===============================*/
  document.addEventListener("keyup", function(e) {
    if (e.key === "PrintScreen") {
      document.body.classList.add("blurred");
      setTimeout(() => {
        document.body.classList.remove("blurred");
      }, 2000);
    }
  });


  // Disable print/save
  document.addEventListener("keydown", function (e) {
    if ((e.ctrlKey || e.metaKey) && (e.key === "p" || e.key === "s")) {
      e.preventDefault();
    }
  });

  // Disable right click
  document.addEventListener("contextmenu", (e) => e.preventDefault());
</script>

</body>
</html>
