
//region UI LOADING SPINNERS
function core_startLoading(divContainer = ""){
    if(_.isEmpty(divContainer)){
        $.LoadingOverlay("show");
    }
    else{
        $(divContainer).LoadingOverlay("show");
    }
}

function core_doneLoading(divContainer = ""){
    if(_.isEmpty(divContainer)){
        $.LoadingOverlay("hide");
    }
    else{
        $(divContainer).LoadingOverlay("hide");
    }
}
//endregion

//region AJAX
//endregion
function core_postDefault(){}
function core_postPage(){}


function core_postForm(targetUrl = "", payload = [], divContainer = "", userCallback, silent = false){
    if(!silent) core_startLoading(divContainer);

    if(_.isEmpty(targetUrl)){
        targetUrl = window.location.href + "post/";
    }
    $.ajax({
        method: "POST",
        url: targetUrl,
        data: payload
    }).done(function(reply){
        if(!silent) core_doneLoading(divContainer);
        core_ajaxReplyHandler(reply, userCallback);
    }).fail(function( jqXHR, errorResponse ) {
        if(!silent) {
            core_doneLoading(divContainer);
            bootbox.alert({message: "Something went wrong", className: "errorBootbox"});
        }
        else{
            console.log("something went wrong");
        }
    });
}

function core_ajaxReplyHandler(reply, userCallback){
    try{
        let data = JSON.parse(reply);
        if(data.success !== undefined){
            core_modalMessage(data.success,"success",function(){
                userCallback(data);
            });
        }
        else if(data.error !== undefined){
            core_modalMessage(data.error,"error",()=>{});
        }
        else{
            bootbox.alert({message:"Something went wrong",className:"errorBootbox"});
        }
    }catch (e) {
        bootbox.alert({message:"Something went wrong",className:"errorBootbox"});
        console.log("ERROR:");
        console.log(e.message);
    }
}

function core_modalMessage(message, type, callback){
    if(typeof callback == 'undefined') callback = function(){};

    let modalTitle = "Alert";
    let modalClass = "";
    if(type === "success"){
        modalTitle = "Success";
        modalClass = "successBootbox";
    }
    else if(type === "error"){
        modalTitle = "Error";
        modalClass = "errorBootbox";
    }

    bootbox.alert({
        size: "large",
        title: modalTitle,
        message: message,
        className: modalClass,
        callback: callback
    });
}