<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/daisyui@5" rel="stylesheet" type="text/css" />
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="icon" href="assets/onecircle.png" type="image/x-icon">
    <title>Power2Connect-Home</title>
</head>
<body>
<div class="m-5">
     <a href="index.php"><button class="sm:w-fit w-full px-3.5 py-2 bg-[#6ccf5f] ease-in-out rounded-lg shadow-[0px_1px_2px_0px_rgba(16,_24,_40,_0.05)] justify-center items-center flex"><span class="px-1.5 text-white text-sm font-medium leading-6">Back to Ai Chatbot</span></button></a>
</div>

    <section class="py-24 relative">
        <div class="w-full max-w-7xl px-4 md:px-5 lg:px-5 mx-auto">
            <div class="w-full justify-start items-center gap-12 grid lg:grid-cols-2 grid-cols-1">
                <div
                    class="w-full justify-center items-start gap-6 grid sm:grid-cols-2 grid-cols-1 lg:order-first order-last">
                    <div class="pt-24 lg:justify-center sm:justify-end justify-start items-start gap-2.5 flex">
                        <img class=" rounded-xl object-cover" src="assets/oneintra.png" alt="about Us image" />
                    </div>
                    <img class="sm:ml-0 ml-auto rounded-xl object-cover" src="assets/oneintra2nd.png"
                        alt="about Us image" />
                </div>
                <div class="w-full flex-col justify-center lg:items-start items-center gap-10 inline-flex">
                    <div class="w-full flex-col justify-center items-start gap-8 flex">
                        <div class="w-full flex-col justify-start lg:items-start items-center gap-3 flex">
                            <h2
                                class="text-gray-900 text-4xl font-bold font-manrope leading-normal lg:text-start text-center">
                                One Intranet Provides Power2Connect Communities</h2>
                            <p class="text-gray-500 text-base font-normal leading-relaxed lg:text-start text-center">
                                One Intranet Corporation is a growing company specializing in the installation of solar energy systems and internet infrastructure across residential, commercial, and underserved communities. We are committed to deliver sustainable and high-performance solutions that meet the demands of today while building a cleaner, more connected tomorrow.</p>
                        </div>
                    </div>
                    <button
                        class="sm:w-fit w-full px-3.5 py-2 bg-[#6ccf5f] transition-all duration-700 ease-in-out rounded-lg shadow-[0px_1px_2px_0px_rgba(16,_24,_40,_0.05)] justify-center items-center flex">
                        <span class="px-1.5 text-white text-sm font-medium leading-6" onclick="my_modal_1.showModal()">Read More</span>
                    </button>
                </div>
            </div>
        </div>
    </section>

<div class="m-5 p-3">
    <details class="collapse bg-base-100 border border-base-300" name="my-accordion-det-1" open>
         <summary class="collapse-title font-semibold">What is Power2Connect Ai</summary>
         <div class="collapse-content text-sm">Power2Connect AI Chatbot is designed to reach out to people in rural areas by offering them immediate and accurate help. One of our main objectives is to provide the users with real-time answers to their queries. If the AI fails to provide a satisfactory response to a query, there is a customer service system in place. The AI will record the conversation and send it to the admin, who can then follow up with the user directly through a call or further assistance.</div>
    </details>
    <details class="collapse bg-base-100 border border-base-300" name="my-accordion-det-1">
         <summary class="collapse-title font-semibold">Moto</summary>
         <div class="collapse-content text-sm">“Regardless of your status in life, You have the power to connect.”</div>
    </details>
    <details class="collapse bg-base-100 border border-base-300" name="my-accordion-det-1">
        <summary class="collapse-title font-semibold">Data Input</summary>
        <div class="collapse-content text-sm">The information you provide will be securely stored in our database and may be used for future contact purposes</div>
    </details>
</div>

    <dialog id="my_modal_1" class="modal">
    <div class="modal-box">
        <h1 class="text-lg font-bold">Mission</h1>
        <p class="py-4 text-gray-500 text-base font-normal leading-relaxed lg:text-start text-center">Our mission is to empower communities by providing reliable access to clean energy and high-speed internet access. Two essential resources for education, economic growth, and overall quality of life. By combining renewable energy technology with modern connectivity, we help bridge the digital divide and reduce environmental impact, creating opportunities for progress in both urban and rural areas.
            <br> <br>
            At the core of our approach is a belief in innovation, accessibility, and long-term impact. Whether it's installing rooftop solar panels to reduce energy bills or deploying internet networks to connect homes, schools, and businesses, One Intranet Corporation is a trusted partner in building smart, resilient, and inclusive communities.
        </p>
        <div class="modal-action">
        <form method="dialog">
            <!-- if there is a button in form, it will close the modal -->
            <button class="btn sm:w-fit w-full px-3.5 py-2 bg-[#6ccf5f] text-white">Close</button>
        </form>
        </div>
    </div>
    </dialog>

<script>
    // Disable image dragging
document.addEventListener("dragstart", function(e) {
    e.preventDefault();
});

// Disable right click
document.addEventListener("contextmenu", function(e) {
    e.preventDefault();
});

// Disable common inspect shortcuts
document.addEventListener("keydown", function(e) {

    // F12
    if (e.key === "F12") {
        e.preventDefault();
    }

    // Ctrl+Shift+I
    if (e.ctrlKey && e.shiftKey && e.key === "I") {
        e.preventDefault();
    }

    // Ctrl+Shift+J
    if (e.ctrlKey && e.shiftKey && e.key === "J") {
        e.preventDefault();
    }

    // Ctrl+U (view source)
    if (e.ctrlKey && e.key === "u") {
        e.preventDefault();
    }

    // Ctrl+Shift+C (inspect element)
    if (e.ctrlKey && e.shiftKey && e.key === "C") {
        e.preventDefault();
    }
});
</script>
</body>
</html>