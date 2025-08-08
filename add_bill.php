<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_login();


// Get printer and maintenance details
$printer_id = $_GET["printer_id"];
$query = "SELECT m.*, i.asset_id, i.name FROM printer_maintenance m
INNER JOIN items i ON i.item_id = m.printer_id
WHERE m.printer_id = '".$printer_id."' ORDER BY id DESC LIMIT 1";
$result_q = $conn->query($query);
$result = $result_q->fetch_assoc()

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Printer Maintenance Records | IT Tracker</title>
    <?php include __DIR__ . '/includes/header_scripts.php'; ?>
    <!-- Then load DataTables CSS and JS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
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
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    
    <div class="container-fluid flex-grow-1">
        <div class="row">
            <?php include __DIR__ . '/includes/sidebar.php'; ?>
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 ">
                    <h1 class="h2">Printer Maintenance Bills for <?php echo $result["asset_id"]." - ".$result["name"] ?></h1>
                </div>
                <h3 class="mb-3 border-bottom"><small><?php echo "Maintenance date: ".$result["service_date"]; ?></small></h3>
                

                <div class="card">
                    <div class="card-body">
                    <div id="statusMsg"></div>
                      <div class="progress mb-3 d-none" id="uploadProgress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar"
                            style="width: 0%" id="progressBar">0%</div>
                      </div>

                      <form id="photoForm">
                        <div id="photoInputs"></div>

                        <button type="button" class="btn btn-secondary mb-3" onclick="addInput()">Add Photo</button><br>
                        <input type="hidden" name="printer_id" value="<?php echo $printer_id; ?>">
                        <button type="submit" class="btn btn-primary">Upload</button> <a href="printer_maintenance.php" class="btn btn-default">Go Back</a>
                      </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>
    <?php include __DIR__ . '/includes/footer_scripts.php'; ?>
    
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