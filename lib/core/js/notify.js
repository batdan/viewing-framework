// affType : info | success | warning | danger | inverse | blackgloss
function myNotify(affType, affMessage) {
    $('.bottom-right').notify({
        message: {
            text: affMessage
        },
        type: 'alert alert-' + affType,
        closable: false
    }).show();
}
