<!DOCTYPE html>
<html>

<head>
    <title>CSV Upload</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">

    <style>
        .excel-style-wrapper {
            overflow-x: auto;
            max-height: 400px;
            border: 1px solid #dee2e6;
            background: white;
        }

        table.excel-style th,
        table.excel-style td {
            padding: 0.5rem;
            font-size: 0.875rem;
            white-space: nowrap;
            border: 1px solid #dee2e6;
            vertical-align: middle;
        }

        table.excel-style thead th {
            background-color: #f1f8ff;
            font-weight: 600;
            position: sticky;
            top: 0;
            z-index: 2;
        }

        div.dt-buttons {
            margin-bottom: 10px;
        }

        #drop-zone {
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        #drop-zone.bg-info {
            border-color: #0dcaf0 !important;
        }
    </style>
</head>

<body class="bg-light py-4">

    <div class="container">
        <!-- <h1 class="mb-4">Upload CSV File</h1> -->

        <form id="upload-form" class="mb-5">
            <div id="drop-zone" class="p-3 border border-primary rounded text-center text-muted mb-3">
                <strong>Drag & drop your CSV file here</strong><br><span class="text-muted">or click to browse</span>
            </div>
            <input type="file" class="form-control d-none" name="file" id="file" required>
            <button type="submit" class="btn btn-primary mt-2 text-center mx-auto d-block" id="upload-btn">Upload File</button>

        </form>

        <h2 class="mb-3">Recent Uploads</h2>
        <div class="table-responsive mb-4">
            <table id="uploads-datatable" class="table table-bordered table-hover table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Time</th>
                        <th>File Name</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="uploads-table"></tbody>
            </table>
        </div>

        <div id="preview-window" class="border rounded bg-white p-3" style="display:none;">
            <h5>File Preview:</h5>
            <iframe id="preview-frame" style="width:100%; height:300px;" class="border"></iframe>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

    <script>
        $(document).ready(function() {
            const previewCache = {};

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            const dropZone = $('#drop-zone');
            const fileInput = $('#file');
            const uploadBtn = $('#upload-btn');
            const originalDropZone = dropZone.html();

            fileInput.on('change', function() {
                const file = this.files[0];
                if (file) {
                    dropZone.html(`<strong>${file.name}</strong>`);
                    uploadBtn.text('Submit').removeClass('btn-primary').addClass('btn-success');
                } else {
                    dropZone.html(originalDropZone);
                    uploadBtn.text('Upload File').removeClass('btn-success').addClass('btn-primary');
                }
            });

            dropZone.on('click', () => fileInput.click());

            dropZone.on('dragover', function(e) {
                e.preventDefault();
                e.stopPropagation();
                dropZone.addClass('bg-info text-white');
            });

            dropZone.on('dragleave drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                dropZone.removeClass('bg-info text-white');
            });

            dropZone.on('drop', function(e) {
                const files = e.originalEvent.dataTransfer.files;
                if (files.length > 0) {
                    fileInput[0].files = files;
                    fileInput.trigger('change');
                    $('#upload-form').submit();
                }
            });

            // Reset UI after success
            $('#upload-form').on('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);

                $.ajax({
                    url: '/upload',
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: () => {
                        alert('Upload successful!');
                        fileInput.val('');
                        dropZone.html(originalDropZone);
                        uploadBtn.text('Upload File').removeClass('btn-success').addClass('btn-primary');
                        fetchUploads();
                    },
                    error: () => alert('Upload failed.')
                });
            });


            function getStatusClass(status) {
                switch (status) {
                    case 'pending':
                        return 'secondary';
                    case 'processing':
                        return 'warning';
                    case 'completed':
                        return 'success';
                    case 'failed':
                        return 'danger';
                    default:
                        return 'secondary';
                }
            }

            function timeAgo(date) {
                const now = new Date();
                const seconds = Math.floor((now - date) / 1000);
                const intervals = [{
                        label: 'year',
                        seconds: 31536000
                    },
                    {
                        label: 'month',
                        seconds: 2592000
                    },
                    {
                        label: 'day',
                        seconds: 86400
                    },
                    {
                        label: 'hour',
                        seconds: 3600
                    },
                    {
                        label: 'minute',
                        seconds: 60
                    },
                    {
                        label: 'second',
                        seconds: 1
                    }
                ];
                for (const i of intervals) {
                    const count = Math.floor(seconds / i.seconds);
                    if (count > 0) return `${count} ${i.label}${count !== 1 ? 's' : ''} ago`;
                }
                return 'just now';
            }

            function generatePreviewHtml(data, batchSize = 30) {
                const lines = data.trim().split('\n');
                const headers = lines[0].split(',');
                const table = document.createElement('table');
                table.className = 'table table-bordered table-hover excel-style';

                const thead = document.createElement('thead');
                const theadRow = document.createElement('tr');
                headers.forEach(h => {
                    const th = document.createElement('th');
                    th.className = 'text-nowrap';
                    th.textContent = h.trim();
                    theadRow.appendChild(th);
                });
                thead.appendChild(theadRow);
                table.appendChild(thead);

                const tbody = document.createElement('tbody');
                table.appendChild(tbody);

                const wrapper = document.createElement('div');
                wrapper.className = 'excel-style-wrapper';
                wrapper.style.position = 'relative';
                wrapper.style.height = '400px';
                wrapper.style.overflow = 'auto';
                wrapper.appendChild(table);

                let currentRow = 1;

                function loadMoreRows() {
                    const limit = Math.min(currentRow + batchSize, lines.length);
                    for (let i = currentRow; i < limit; i++) {
                        const cols = lines[i].split(',');
                        const tr = document.createElement('tr');
                        cols.forEach(c => {
                            const td = document.createElement('td');
                            td.className = 'text-nowrap';
                            td.textContent = c.trim();
                            tr.appendChild(td);
                        });
                        tbody.appendChild(tr);
                    }
                    currentRow = limit;
                    if (currentRow >= lines.length) {
                        wrapper.removeEventListener('scroll', onScroll);
                    }
                }

                function onScroll() {
                    if (wrapper.scrollTop + wrapper.clientHeight >= wrapper.scrollHeight - 10 && currentRow < lines.length) {
                        loadMoreRows();
                    }
                }

                loadMoreRows();
                wrapper.addEventListener('scroll', onScroll);

                return wrapper;
            }

            window.previewFile = function(filename) {
                $('#preview-window').show().html('<p class="text-warning">Loading preview...</p>');
                if (previewCache[filename]) {
                    $('#preview-window').empty().append($('<h5>File Preview:</h5>')).append(previewCache[filename]);
                } else {
                    $.get(`/preview-text/${filename}`, function(data) {
                        const element = generatePreviewHtml(data);
                        previewCache[filename] = element;
                        $('#preview-window').empty().append($('<h5>File Preview:</h5>')).append(element);
                    }).fail(() => {
                        $('#preview-window').html('<p class="text-danger">Could not load file preview.</p>');
                    });
                }
            };

            function fetchUploads() {
                $.get('/uploads', function(data) {
                    let table = $('#uploads-datatable').DataTable();
                    if ($.fn.DataTable.isDataTable('#uploads-datatable')) {
                        table.clear();
                    }

                    const filenamesToPreload = [];

                    data.forEach(row => {
                        let viewButton = '';
                        if (row.status === 'completed') {
                            const filename = row.file_path.split('/').pop();
                            viewButton = `<button class="btn btn-sm btn-success" onclick="previewFile('${filename}')">View</button>`;
                            if (!previewCache[filename]) filenamesToPreload.push(filename);
                        }

                        const createdAtUTC = new Date(row.updated_at);
                        const formattedMYTime = createdAtUTC.toLocaleString('en-MY', {
                            timeZone: 'Asia/Kuala_Lumpur',
                            year: 'numeric',
                            month: 'short',
                            day: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit',
                            hour12: true
                        });

                        const timeText = `${formattedMYTime}<br><small class="text-muted">${timeAgo(createdAtUTC)}</small>`;
                        const statusBadge = `<span class="badge bg-${getStatusClass(row.status)}">${row.status}</span>`;

                        table.row.add([
                            timeText,
                            row.file_name,
                            statusBadge,
                            viewButton
                        ]);
                    });

                    table.draw();
                    preloadInChunks(filenamesToPreload, 2);
                });
            }

            function preloadInChunks(filenames, chunkSize = 2) {
                let index = 0;

                function loadNextChunk() {
                    const chunk = filenames.slice(index, index + chunkSize);
                    if (!chunk.length) return;
                    const requests = chunk.map(filename =>
                        $.get(`/preview-text/${filename}`)
                        .then(data => previewCache[filename] = generatePreviewHtml(data))
                        .catch(() => previewCache[filename] = '<p class="text-danger">Failed to preload file.</p>')
                    );
                    Promise.all(requests).then(() => {
                        index += chunkSize;
                        loadNextChunk();
                    });
                }
                loadNextChunk();
            }

            fetchUploads();
            setInterval(fetchUploads, 10000);
        });
    </script>
</body>

</html>