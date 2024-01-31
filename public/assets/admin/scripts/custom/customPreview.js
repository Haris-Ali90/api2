
function preview(event) {

    let img_src = event.src;

    let html = '<div id="preview" class="modal">';
    html+=' <span class="cancel-btn" onClick="CloseLightBox()">X</span>';
    html+=' <img src="'+img_src+ '" class="img" id=""> ';
    html+=' </div> ';
    document.getElementsByTagName("body")[0].insertAdjacentHTML("beforeend", html );
}

function CloseLightBox() {
    document.getElementById("preview").remove();
}