<script src="{{ asset('js/overlayscrollbars.browser.es6.min.js') }}"></script> <!--end::Third Party Plugin(OverlayScrollbars)--><!--begin::Required Plugin(popperjs for Bootstrap 5)-->
<script src="{{ asset('js/popper.min.js') }}"></script> <!--end::Required Plugin(popperjs for Bootstrap 5)--><!--begin::Required Plugin(Bootstrap 5)-->
<script src="{{ asset('js/bootstrap.min.js') }}"></script> <!--end::Required Plugin(Bootstrap 5)--><!--begin::Required Plugin(AdminLTE)-->
<script src="{{ asset('js/adminlte.js') }}"></script> <!--end::Required Plugin(AdminLTE)--><!--begin::OverlayScrollbars Configure-->
<script src="{{ asset('js/jquery-3.7.1.min.js') }}"></script>
<script src="{{ asset('plugins/loader/waitMe.js') }}"></script>
<script src="{{ asset('plugins/DataTables/js/dataTables.js') }}"></script>
<script src="{{ asset('plugins/DataTables/js/dataTables.bootstrap5.js') }}"></script>
<script src="{{ asset('plugins/DataTables/js/dataTables.responsive.js') }}"></script>
<script src="{{ asset('plugins/DataTables/js/responsive.bootstrap5.js') }}"></script>
<script src="{{ asset('plugins/DataTables/js/dataTables.buttons.js') }}"></script>
<script src="{{ asset('plugins/DataTables/js/buttons.bootstrap5.js') }}"></script>
<script src="{{ asset('plugins/DataTables/button/buttons.html5.min.js') }}"></script>
<script src="{{ asset('plugins/DataTables/button/buttons.print.min.js') }}"></script>
<script src="{{ asset('plugins/DataTables/button/buttons.colVis.min.js') }}"></script>
<script src="{{ asset('plugins/DataTables/lib/jszip.min.js') }}"></script>
<script src="{{ asset('plugins/DataTables/lib/pdfmake.min.js') }}"></script>
<script src="{{ asset('plugins/DataTables/lib/vfs_fonts.js') }}"></script>
<script src="{{ asset('plugins/fontawesome6.7.2/js/all.min.js') }}"></script>
<script src="{{ asset('plugins/select2/select2.full.min.js') }}"></script>
<script src="{{ asset('js/moment.min.js') }}"></script>
<script src="{{ asset('js/moment-with-locales.js') }}"></script>
<script src="{{ asset('plugins/daterangepicker-master/daterangepicker.js') }}"></script>
<script src="{{ asset('plugins/bootstrap-datepicker/bootstrap-datepicker.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('plugins/tooltipster/dist/js/tooltipster.bundle.min.js') }}"></script>
{{-- <script src="{{ asset('plugins/chart.umd.min.js') }}"></script>
<script src="{{ asset('plugins/fullcalendar-6.0.2/dist/index.global.min.js') }}"></script>
<script src="{{ asset('plugins/fullcalendar-6.0.2/package/core/locales/id.global.js') }}"></script> --}}
<script src="{{ asset('plugins/EasyAutocomplete/dist/jquery.easy-autocomplete.min.js') }}" type="text/javascript"></script>
{{-- <script src="{{ asset('plugins/chartjs-plugin-datalabels.js') }}"></script> --}}
<script src="{{ asset('js/tippy-bundle.umd.min.js') }}"></script>
<script src="{{ asset('plugins/xlsx.full.min.js') }}"></script>
<script src="{{ asset('plugins/quill/quill.min.js') }}"></script>
<script src="{{ asset('plugins/pdf/pdf.min.js') }}"></script>
<script src="{{ asset('plugins/pdf/pdf-lib.min.js') }}"></script>
<script src="{{ asset('plugins/webdatarocks-1.4.19/webdatarocks.toolbar.min.js') }}"></script>
<script src="{{ asset('plugins/webdatarocks-1.4.19/webdatarocks.js') }}"></script>
<script src="{{ asset('js/bootstrap3-typeahead.min.js') }}"></script>
<script src="{{ asset('plugins/sweetalert2/sweetalert2.all.min.js') }}"></script>
<script src="{{ asset('plugins/jstree/jstree.min.js') }}"></script>
<script>
        // Example starter JavaScript for disabling form submissions if there are invalid fields
        (function () {
        'use strict'

        // Fetch all the forms we want to apply custom Bootstrap validation styles to
        var forms = document.querySelectorAll('.needs-validation')

        // Loop over them and prevent submission
        Array.prototype.slice.call(forms)
            .forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
                }

                form.classList.add('was-validated')
            }, false)
            })
        })()
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        const SELECTOR_SIDEBAR_WRAPPER = ".sidebar-wrapper";
        const Default = {
            scrollbarTheme: "os-theme-light",
            scrollbarAutoHide: "leave",
            scrollbarClickScroll: true,
        };
        document.addEventListener("DOMContentLoaded", function() {
            const sidebarWrapper = document.querySelector(SELECTOR_SIDEBAR_WRAPPER);
            if (
                sidebarWrapper &&
                typeof OverlayScrollbarsGlobal?.OverlayScrollbars !== "undefined"
            ) {
                OverlayScrollbarsGlobal.OverlayScrollbars(sidebarWrapper, {
                    scrollbars: {
                        theme: Default.scrollbarTheme,
                        autoHide: Default.scrollbarAutoHide,
                        clickScroll: Default.scrollbarClickScroll,
                    },
                });
            }
        });
        // Color Mode Toggler
        (() => {
        "use strict";

        const storedTheme = localStorage.getItem("theme");

        const getPreferredTheme = () => {
            if (storedTheme) {
            return storedTheme;
            }

            return window.matchMedia("(prefers-color-scheme: dark)").matches
            ? "dark"
            : "light";
        };

        const setTheme = function (theme) {
            if (
            theme === "auto" &&
            window.matchMedia("(prefers-color-scheme: dark)").matches
            ) {
            document.documentElement.setAttribute("data-bs-theme", "dark");
            } else {
            document.documentElement.setAttribute("data-bs-theme", theme);
            }
        };

        setTheme(getPreferredTheme());

        const showActiveTheme = (theme, focus = false) => {
            const themeSwitcher = document.querySelector("#bd-theme");
            
            if (!themeSwitcher) {
            return;
            }

            const themeSwitcherText = document.querySelector("#bd-theme-text");
            const activeThemeIcon = document.querySelector(".theme-icon-active i");
            const btnToActive = document.querySelector(
            `[data-bs-theme-value="${theme}"]`
            );
            if (!btnToActive) return;
            const svgOfActiveBtn = btnToActive.querySelector("i").getAttribute("class");

            for (const element of document.querySelectorAll("[data-bs-theme-value]")) {
            element.classList.remove("active");
            element.setAttribute("aria-pressed", "false");
            }

            btnToActive.classList.add("active");
            btnToActive.setAttribute("aria-pressed", "true");
            activeThemeIcon.setAttribute("class", svgOfActiveBtn);
            const themeSwitcherLabel = `${themeSwitcherText.textContent} (${btnToActive.dataset.bsThemeValue})`;
            themeSwitcher.setAttribute("aria-label", themeSwitcherLabel);

            if (focus) {
            themeSwitcher.focus();
            }
        };

        window
            .matchMedia("(prefers-color-scheme: dark)")
            .addEventListener("change", () => {
            if (storedTheme !== "light" || storedTheme !== "dark") {
                setTheme(getPreferredTheme());
            }
            });

        window.addEventListener("DOMContentLoaded", () => {
            showActiveTheme(getPreferredTheme());

            for (const toggle of document.querySelectorAll("[data-bs-theme-value]")) {
            toggle.addEventListener("click", () => {
                const theme = toggle.getAttribute("data-bs-theme-value");
                localStorage.setItem("theme", theme);
                setTheme(theme);
                showActiveTheme(theme, true);
            });
            }
        });
        })();
        $( document ).ready(function() {$('#bdy').addClass('sidebar')});
    </script> <!--end::OverlayScrollbars Configure--> <!--end::Script-->