window.ajaxRequest = function ({
    url,
    type = "GET",
    data = {},
    before = null,
    success = null,
    error = null,
    complete = null,
    dataType = "json",
    // FormData (upload file): kirim processData: false, contentType: false
    processData,
    contentType,
}) {
    if (typeof before === "function") {
        before();
    }

    $.ajax({
        url,
        type,
        data,
        dataType,
        processData,
        contentType,

        success(res) {
            if (typeof success === "function") {
                success(res);
            }
        },

        error(xhr) {
            if (typeof error === "function") {
                error(xhr);
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Oops...",
                    text: xhr.responseJSON?.message ?? "Terjadi kesalahan.",
                });
            }
        },

        complete() {
            if (typeof complete === "function") {
                complete();
            }
        },
    });
};
