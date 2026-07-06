{{--
    Shared training report scripts: date picker, markdown editors, and
    prevent-close guard.

    Expects:
    - $datepickerDefault  string       Default date in d/m/Y format.
    - $datepickerMaxDate  string|null  Optional max selectable date (Y-m-d). Omitted when null.
--}}

<!-- Flatpickr -->
@vite(['resources/js/flatpickr.js', 'resources/sass/flatpickr.scss'])
<script>
    document.addEventListener("DOMContentLoaded", function () {
        document.querySelector('.datepicker').flatpickr({
            disableMobile: true,
            minDate: "{!! date('Y-m-d', strtotime('-1 months')) !!}",
            @isset($datepickerMaxDate)
                maxDate: "{{ $datepickerMaxDate }}",
            @endisset
            dateFormat: "d/m/Y",
            defaultDate: "{{ $datepickerDefault }}",
            locale: { firstDayOfWeek: 1 }
        });
    });
</script>

<!-- Markdown Editor -->
@vite(['resources/js/easymde.js', 'resources/sass/easymde.scss'])
<script>
    document.addEventListener("DOMContentLoaded", function () {
        var simplemde1 = new EasyMDE({
            element: document.getElementById("contentBox"),
            status: false,
            toolbar: ["bold", "italic", "heading-3", "|", "quote", "unordered-list", "ordered-list", "|", "link", "preview", "side-by-side", "fullscreen", "|", "guide"],
            insertTexts: {
                link: ["[","](link)"],
            }
        });
        var simplemde2 = new EasyMDE({
            element: document.getElementById("contentimprove"),
            status: false,
            toolbar: ["bold", "italic", "heading-3", "|", "quote", "unordered-list", "ordered-list", "|", "link", "preview", "side-by-side", "fullscreen", "|", "guide"],
            insertTexts: {
                link: ["[","](link)"],
            }
        });

        // Snapshot initial editor state so we only warn on genuine changes.
        var initialContent = simplemde1.value();
        var initialContentImprove = simplemde2.value();

        var formSubmitted = false;
        document.addEventListener("submit", function (event) {
            if (event.target.tagName === "FORM") {
                formSubmitted = true;
            }
        });

        // Confirm closing window if there are unsaved changes
        window.addEventListener('beforeunload', function (e) {
            if (!formSubmitted && (simplemde1.value() !== initialContent || simplemde2.value() !== initialContentImprove)) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    });
</script>
