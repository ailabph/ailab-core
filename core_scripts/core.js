
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
function core_postDefault(dataName,data,action,callback,silent = false,divContainer = ""){
    if(!core_notEmpty(dataName) || !core_notEmpty(action)){
        throw new Error("some parameters is not provided");
    }
    core_postForm(core_URL+"/data.php",data+"&"+action+"="+dataName,divContainer,callback,silent,true);
}
function core_postPage(dataName,data,action,callback,divContainer = "", silent = false, manualHandleReply = false){
    if(!core_notEmpty(dataName) || !core_notEmpty(action)){
        throw new Error("some parameters is not provided");
    }
    core_postForm(core_URL+"/",data+"&action=dataName",divContainer,callback,silent,manualHandleReply);
}

function core_postForm(targetUrl = "", payload = [], divContainer = "", userCallback, silent = false, manualHandleReply = false){
    if(!silent) core_startLoading(divContainer);

    if(_.isEmpty(targetUrl)){
        targetUrl = window.location.href + "post/";
    }
    if(Cookies.get('fp').length){
        payload += "&fp="+Cookies.get('fp');
    }
    $.ajax({
        method: "POST",
        url: targetUrl,
        data: payload
    }).done(function(reply){
        if(!silent) core_doneLoading(divContainer);
        if(!manualHandleReply) core_ajaxReplyHandler(reply, userCallback, silent);
        else userCallback(reply);
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

function core_ajaxReplyHandler(reply, userCallback, silent = false){
    try{
        let data = JSON.parse(reply);
        if(data.success !== undefined){
            if(silent){
                userCallback(data);
            }
            else{
                core_modalMessage(data.success,"success",function(){
                    userCallback(data);
                });
            }
        }
        else if(data.error !== undefined){
            if(silent){
                console.log("ajax error response:"+data.error);
            }
            else{
                core_modalMessage(data.error,"error",()=>{});
            }
        }
        else{
            if(silent){
                console.log("something went wrong with the ajax request");
            }
            else{
                bootbox.alert({message:"Something went wrong",className:"errorBootbox"});
            }
        }
    }catch (e) {
        console.log("ERROR:");
        console.log(e.message);
    }
}

function core_modalMessage(msg, type, _callback, customTitle){
    if(typeof msg === "undefined") {
        return;
    }
    if(typeof _callback == 'undefined') {
        _callback = function () {};
    }

    let box = bootbox.alert({
        size: "large",
        title: "Title",
        message: msg,
        callback: _callback
    })
    if(type === "error"){
        if(typeof customTitle != "undefined"){
            box.find('.modal-title').text(customTitle);
        }else{
            box.find('.modal-title').text("Error");
        }
        box.find('.modal-header').addClass("alert-danger");
        box.find('.btn-primary').removeClass("btn-primary").addClass("btn-danger");
    }
    if(type === "success" || type === "good" || type === "ok"){
        if(typeof customTitle != "undefined"){
            box.find('.modal-title').text(customTitle);
        }else{
            box.find('.modal-title').text("Success");
        }
        box.find('.modal-header').addClass("alert-success");
        box.find('.btn-primary').removeClass("btn-primary").addClass("btn-success");
    }
    if(type === "warning" || type === "notice"){
        if(typeof customTitle != "undefined"){
            box.find('.modal-title').text(customTitle);
        }else{
            box.find('.modal-title').text("Notice");
        }
        box.find('.modal-header').addClass("alert-warning");
        box.find('.btn-primary').removeClass("btn-primary").addClass("btn-warning");
    }
}

function core_modalConfirm(msg,callback){
    if(typeof callback == 'undefined') callback = function(){};
    let box = bootbox.confirm({
        title: "Notice",
        size: "large",
        message: msg,
        callback: function(reply){
            if(reply){
                callback();
            }
        }
    })
    box.find('.modal-header').addClass("alert-warning");
    box.find('.btn-primary').removeClass("btn-primary").addClass("btn-warning");
}

// UTILITIES
function core_notEmpty(data){
    return data && typeof data === "string" && data.trim() !== "";
}