<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Template Name</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $template->name) }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Channel</label>
                <select name="channel" class="form-select" required>
                    @foreach (['email' => 'Email', 'sms' => 'SMS', 'whatsapp' => 'WhatsApp'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('channel', $template->channel) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <div class="form-check">
                    <input type="checkbox" name="is_active" class="form-check-input" value="1" {{ old('is_active', $template->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label">Active</label>
                </div>
            </div>
            <div class="col-12">
                <label class="form-label">Subject</label>
                <input type="text" name="subject" class="form-control" value="{{ old('subject', $template->subject) }}">
                <div class="form-text">Required mainly for email templates. SMS and WhatsApp can leave this blank.</div>
            </div>
            <div class="col-12">
                <label class="form-label">Message Body</label>
                <textarea name="body" id="message-template-body" class="form-control" rows="8" required>{{ old('body', $template->body) }}</textarea>
                <div class="form-text">You can use placeholders like `{{ '{firstName}' }}`, `{{ '{company}' }}`, `{{ '{email}' }}`.</div>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button class="btn btn-primary">{{ $template->exists ? 'Update Template' : 'Save Template' }}</button>
            <a href="{{ route('message-templates.index') }}" class="btn btn-outline-secondary">Back</a>
        </div>
    </div>
</div>

<script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var editorElement = document.getElementById('message-template-body');

        if (!editorElement || editorElement.dataset.ckeditorInitialized === 'true') {
            return;
        }

        function LaravelUploadAdapter(loader) {
            this.loader = loader;
        }

        LaravelUploadAdapter.prototype.upload = function () {
            return this.loader.file.then(function (file) {
                return new Promise(function (resolve, reject) {
                    var data = new FormData();
                    var xhr = new XMLHttpRequest();

                    data.append('upload', file);

                    xhr.open('POST', '{{ route('message-templates.upload-image') }}', true);
                    xhr.responseType = 'json';
                    xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

                    xhr.addEventListener('error', function () {
                        reject('Image upload failed.');
                    });

                    xhr.addEventListener('abort', function () {
                        reject('Image upload aborted.');
                    });

                    xhr.addEventListener('load', function () {
                        var response = xhr.response || {};

                        if (xhr.status < 200 || xhr.status >= 300 || !response.url) {
                            reject(response.message || 'Image upload failed.');
                            return;
                        }

                        resolve({
                            default: response.url
                        });
                    });

                    xhr.send(data);
                });
            });
        };

        LaravelUploadAdapter.prototype.abort = function () {
        };

        ClassicEditor
            .create(editorElement, {
                extraPlugins: [
                    function (editor) {
                        editor.plugins.get('FileRepository').createUploadAdapter = function (loader) {
                            return new LaravelUploadAdapter(loader);
                        };
                    }
                ],
                toolbar: [
                    'heading',
                    '|',
                    'bold',
                    'italic',
                    'link',
                    'bulletedList',
                    'numberedList',
                    '|',
                    'uploadImage',
                    'blockQuote',
                    'insertTable',
                    'undo',
                    'redo'
                ],
                image: {
                    toolbar: [
                        'imageTextAlternative',
                        'imageStyle:inline',
                        'imageStyle:block',
                        'imageStyle:side'
                    ]
                }
            })
            .then(function (editor) {
                editorElement.dataset.ckeditorInitialized = 'true';
                editor.model.document.on('change:data', function () {
                    editorElement.value = editor.getData();
                });
            })
            .catch(function (error) {
                console.error(error);
            });
    });
</script>
