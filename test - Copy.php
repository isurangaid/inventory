<?php
$config = require 'includes/config.php';
echo __DIR__;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Responsive Image Upload with Progress</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .preview-img {
      width: 100%;
      max-height: 120px;
      object-fit: cover;
      border: 1px solid #ccc;
    }
    .preview-container {
      position: relative;
      display: inline-block;
      width: 100%;
    }
    .delete-icon {
      position: absolute;
      top: -8px;
      right: -8px;
      background-color: #dc3545;
      color: #fff;
      border-radius: 50%;
      padding: 2px 6px;
      font-weight: bold;
      cursor: pointer;
      font-size: 14px;
    }
  </style>
</head>
<body class="container py-4">
  <h2 class="mb-4">Upload or Take Photos</h2>

  <div id="statusMsg"></div>
  <div class="progress mb-3 d-none" id="uploadProgress">
    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar"
         style="width: 0%" id="progressBar">0%</div>
  </div>

  <form id="photoForm">
    <div id="photoInputs"></div>

    <button type="button" class="btn btn-secondary mb-3" onclick="addInput()">Add Photo</button><br>
    <button type="submit" class="btn btn-primary">Upload</button>
  </form>

  <script>
    let inputCount = 0;

    function addInput() {
      const id = 'photo_' + inputCount++;
      const wrapper = document.createElement('div');
      wrapper.className = 'row g-3 mb-3';
      wrapper.dataset.id = id;

      const colInput = document.createElement('div');
      colInput.className = 'col-12 col-md-8';

      const inputGroup = document.createElement('div');
      inputGroup.className = 'input-group';

      const input = document.createElement('input');
      input.type = 'file';
      input.name = 'photos[]';
      input.accept = 'image/*'; // <-- allows gallery & camera
      input.className = 'form-control image-input';
      input.required = true;
      input.dataset.id = id;

      input.addEventListener('change', () => showPreview(input, id));

      const removeBtn = document.createElement('button');
      removeBtn.type = 'button';
      removeBtn.textContent = 'Remove';
      removeBtn.className = 'btn btn-danger';
      removeBtn.onclick = () => removeImage(id);

      inputGroup.appendChild(input);
      inputGroup.appendChild(removeBtn);
      colInput.appendChild(inputGroup);

      const colPreview = document.createElement('div');
      colPreview.className = 'col-12 col-md-4';
      colPreview.id = 'preview_' + id;

      wrapper.appendChild(colInput);
      wrapper.appendChild(colPreview);
      document.getElementById('photoInputs').appendChild(wrapper);
    }

    function showPreview(input, id) {
      if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
          const previewArea = document.getElementById('preview_' + id);
          previewArea.innerHTML = '';

          const container = document.createElement('div');
          container.className = 'preview-container';

          const img = document.createElement('img');
          img.src = e.target.result;
          img.className = 'preview-img';

          const del = document.createElement('span');
          del.className = 'delete-icon';
          del.innerHTML = '&times;';
          del.onclick = () => removeImage(id);

          container.appendChild(img);
          container.appendChild(del);
          previewArea.appendChild(container);
        };
        reader.readAsDataURL(input.files[0]);
      }
    }

    function removeImage(id) {
      const row = document.querySelector(`[data-id="${id}"]`);
      if (row) row.remove();
    }

    document.getElementById('photoForm').addEventListener('submit', function (e) {
	  e.preventDefault();

	  const form = e.target;
	  const formData = new FormData(form);
	  const inputs = form.querySelectorAll("input[type='file']");

	  let hasFile = false;
	  for (const input of inputs) {
		if (input.files.length > 0) {
		  hasFile = true;
		  break;
		}
	  }

	  const progress = document.getElementById('uploadProgress');
	  const progressBar = document.getElementById('progressBar');
	  const statusMsg = document.getElementById('statusMsg');

	  if (!hasFile) {
		statusMsg.innerHTML = '<div class="alert alert-danger">Please select at least one image.</div>';
		progress.classList.add('d-none');
		progressBar.style.width = '0%';
		progressBar.innerHTML = '0%';
		return;
	  }

	  const xhr = new XMLHttpRequest();
	  xhr.open('POST', 'upload.php', true);

	  progress.classList.remove('d-none');
	  progressBar.style.width = '0%';
	  progressBar.innerHTML = '0%';

	  xhr.upload.onprogress = function (e) {
		if (e.lengthComputable) {
		  const percent = Math.round((e.loaded / e.total) * 100);
		  progressBar.style.width = percent + '%';
		  progressBar.innerHTML = percent + '%';
		}
	  };

	  xhr.onload = function () {
      const statusMsg = document.getElementById('statusMsg');
      const progress = document.getElementById('uploadProgress');
      const progressBar = document.getElementById('progressBar');

      if (xhr.status === 200 && xhr.responseText.trim() === 'OK') {
        statusMsg.innerHTML = '<div class="alert alert-success">Upload completed!</div>';

        // Clear inputs and previews
        document.getElementById('photoInputs').innerHTML = '';
        addInput(); // Add one fresh empty field

        // Hide and reset progress bar
        progress.classList.add('d-none');
        progressBar.style.width = '0%';
        progressBar.innerHTML = '0%';
      } else {
        statusMsg.innerHTML = '<div class="alert alert-danger">Upload failed: ' + xhr.responseText + '</div>';
        progress.classList.add('d-none');
        progressBar.style.width = '0%';
        progressBar.innerHTML = '0%';
      }
    };

	  xhr.send(formData);
	});

  </script>
</body>
</html>
