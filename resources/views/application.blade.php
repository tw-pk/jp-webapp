<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8"/>
    <link rel="icon" href="{{ asset('vuexy-logo.svg') }}"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>JotPhone</title>
    <link rel="stylesheet" type="text/css" href="{{ asset('loader.css') }}"/>
    <script src="https://js.stripe.com/v3/"></script>
    @vite(['resources/js/main.js'])
</head>

<body>
<div id="app">
    <div id="loading-bg">
        <div class="loading-logo">
            <!-- SVG Logo -->
            <svg width="86" height="48" viewBox="0 0 34 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M17 23.6875C23.4548 23.6875 28.6875 18.4548 28.6875 12C28.6875 5.54517 23.4548 0.3125 17 0.3125C10.5452 0.3125 5.3125 5.54517 5.3125 12C5.3125 18.4548 10.5452 23.6875 17 23.6875Z" fill="#38A6E3"/>
                <path opacity="0.1" d="M22.5544 17.6473L19.4131 14.5085L11.3285 6.42395C10.7855 6.91582 10.3034 7.43696 10.3034 7.74867C10.3034 9.59205 11.913 12.256 14.0169 14.4939C14.3286 14.83 14.6549 15.1538 14.9861 15.4655L15.0226 15.4996L22.0616 22.5386C23.2357 21.9736 24.3 21.2174 25.2162 20.3117L22.5544 17.6498V17.6473Z" fill="black"/>
                <path opacity="0.1" d="M21.0639 13.3932L15.585 7.9142L13.0622 5.38902C12.9453 5.27454 12.7651 5.26237 12.6336 5.3598C12.4534 5.49375 12.1807 5.70317 11.886 5.94667L25.72 19.7806C25.9937 19.474 26.2505 19.1523 26.4911 18.818L23.6014 15.9282L21.0639 13.3932Z" fill="black"/>
                <path d="M20.5928 13.4005C20.398 13.6103 20.1938 13.8249 20.0007 14.0181C19.9933 14.0255 19.9871 14.0316 19.9799 14.0388L23.0368 17.0957C23.2796 16.8061 23.4907 16.5388 23.6289 16.3598C23.7295 16.2296 23.7169 16.0456 23.6006 15.9293L21.0635 13.3922C20.9323 13.261 20.7189 13.2646 20.5928 13.4005Z" fill="white"/>
                <path d="M19.4127 14.5077C18.478 14.8182 17.2994 14.519 16.2913 13.7016C14.9556 12.6185 14.2212 10.9008 14.4973 9.59232L11.3291 6.42407C10.7846 6.91542 10.3036 7.43713 10.3036 7.74973C10.3036 11.6177 17.3975 19.0983 21.2439 18.69C21.5518 18.6573 22.0662 18.181 22.5536 17.6485L19.4127 14.5077Z" fill="white"/>
                <path d="M12.6347 5.35899C12.4533 5.49496 12.181 5.70417 11.8866 5.9456L14.9369 8.99591C14.9386 8.99425 14.9399 8.9928 14.9416 8.99114C15.1388 8.7939 15.36 8.58495 15.5762 8.3863C15.7133 8.26038 15.7173 8.04615 15.5858 7.91458L13.0614 5.39023C12.9463 5.27513 12.7649 5.26136 12.6347 5.35899Z" fill="white"/>
            </svg>

{{--            <svg width="86" height="48" viewBox="0 0 34 24" fill="none" xmlns="http://www.w3.org/2000/svg">--}}
{{--                <path fill-rule="evenodd" clip-rule="evenodd"--}}
{{--                      d="M0.00183571 0.3125V7.59485C0.00183571 7.59485 -0.141502 9.88783 2.10473 11.8288L14.5469 23.6837L21.0172 23.6005L19.9794 10.8126L17.5261 7.93369L9.81536 0.3125H0.00183571Z"--}}
{{--                      fill="var(--initial-loader-color)"/>--}}
{{--                <path opacity="0.06" fill-rule="evenodd" clip-rule="evenodd"--}}
{{--                      d="M8.17969 17.7762L13.3027 3.75173L17.589 8.02192L8.17969 17.7762Z" fill="#161616"/>--}}
{{--                <path opacity="0.06" fill-rule="evenodd" clip-rule="evenodd"--}}
{{--                      d="M8.58203 17.2248L14.8129 5.24231L17.6211 8.05247L8.58203 17.2248Z" fill="#161616"/>--}}
{{--                <path fill-rule="evenodd" clip-rule="evenodd"--}}
{{--                      d="M8.25781 17.6914L25.1339 0.3125H33.9991V7.62657C33.9991 7.62657 33.8144 10.0645 32.5743 11.3686L21.0179 23.6875H14.5487L8.25781 17.6914Z"--}}
{{--                      fill="var(--initial-loader-color)"/>--}}
{{--            </svg>--}}
        </div>
        <div class=" loading">
            <div class="effect-1 effects"></div>
            <div class="effect-2 effects"></div>
            <div class="effect-3 effects"></div>
        </div>
    </div>
</div>

<script>
    const loaderColor = localStorage.getItem('vuexy-initial-loader-bg') || '#FFFFFF'
    const primaryColor = localStorage.getItem('vuexy-initial-loader-color') || '#38A6E3'

    if (loaderColor)
        document.documentElement.style.setProperty('--initial-loader-bg', loaderColor)

    if (primaryColor)
        document.documentElement.style.setProperty('--initial-loader-color', primaryColor)
</script>
</body>

</html>
