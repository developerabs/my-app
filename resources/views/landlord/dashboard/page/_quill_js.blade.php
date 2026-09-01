    <script src="{{asset('backend')}}/assets/libs/quill/quill.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/quill-image-resize-module@3.0.0/image-resize.min.js"></script>

    <script>

        function initQuill(){
            Quill.register('modules/imageResize', window.ImageResize.default);

            window.quill = new Quill('#content', {
                theme: 'snow',
                modules: {
                    toolbar: {
                        container: [
                            ['bold', 'italic', 'underline', 'strike'],
                            ['blockquote', 'code-block'],
                            [{ 'header': 1 }, { 'header': 2 }],
                            [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                            [{ 'script': 'sub' }, { 'script': 'super' }],
                            [{ 'indent': '-1' }, { 'indent': '+1' }],
                            [{ 'direction': 'rtl' }],
                            [{ 'size': ['small', false, 'large', 'huge'] }],
                            [{ 'color': [] }, { 'background': [] }],
                            [{ 'align': [] }],
                            ['link', 'image', 'video']
                        ],
                        handlers: {
                            image: imageHandler
                        }
                    },
                    imageResize: {
                        modules: [ 'Resize', 'DisplaySize', 'Toolbar' ]
                    }
                }
            });

            const editorDiv = document.getElementById('content');
            const sourceTextarea = document.getElementById('source-container');
            const toggleBtn = document.getElementById('toggle-source');

            let showingSource = false;

            toggleBtn.addEventListener('click', () => {
                if (!showingSource) {
                    // Show HTML source
                    sourceTextarea.value = editorDiv.querySelector('.ql-editor').innerHTML;
                    editorDiv.style.display = 'none';
                    sourceTextarea.style.display = 'block';
                    toggleBtn.innerText = 'Back to Editor';

                    // Update hidden input
                    $('#content_input').val(sourceTextarea.value);
                } else {
                    // Back to Quill editor
                    editorDiv.querySelector('.ql-editor').innerHTML = sourceTextarea.value;
                    sourceTextarea.style.display = 'none';
                    editorDiv.style.display = 'block';
                    toggleBtn.innerText = 'Toggle HTML';

                    // Update hidden input
                    $('#content_input').val(quill.root.innerHTML);
                }
                showingSource = !showingSource;
            });


            $('form').on('submit', function() {
                const content = showingSource ? sourceTextarea.value : quill.root.innerHTML;
                $('#content_input').val(content);
            });

            quill.on('text-change', function() {
                $('#content_input').val(quill.root.innerHTML);
            });
        }

    </script>