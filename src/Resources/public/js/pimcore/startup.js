pimcore.registerNS("pimcore.plugin.ValanticPimcoreFormsBundle");

pimcore.plugin.ValanticPimcoreFormsBundle = Class.create(pimcore.plugin.admin, {
    getClassName: function () {
        return "pimcore.plugin.ValanticPimcoreFormsBundle";
    },

    initialize: function () {
        pimcore.plugin.broker.registerPlugin(this);
    },

    pimcoreReady: function (params, broker) {
        // alert("ValanticPimcoreFormsBundle ready!");
    }
});

var ValanticPimcoreFormsBundlePlugin = new pimcore.plugin.ValanticPimcoreFormsBundle();
