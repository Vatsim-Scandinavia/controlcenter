<!-- Zulu Clock -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        setInterval(function (){
            var currentdate = new Date()
            var datetime = ('0'+currentdate.getUTCHours()).substr(-2,2) + ":" + ('0'+currentdate.getUTCMinutes()).substr(-2,2)
            document.querySelector('.zulu-clock').innerHTML = datetime + 'z'
        }, 1000)
    })
</script>
