@include('inc/header')
<div id="google_translate_element"></div>
            <script type="text/javascript">
                function googleTranslateElementInit() {
                    new google.translate.TranslateElement({pageLanguage: 'ur'}, 'google_translate_element');
                }
            </script>
@yield('content')
@include('inc/footer')
