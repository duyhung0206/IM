var lowStockPOControl = Class.create();
lowStockPOControl.prototype = {
        //initialize object
    initialize: function(grid, supplierFilter, selectSupplierMessage, changeSupplierUrl, createPOUrl){
        this.grid = grid;
        this.supplierFilter = supplierFilter;
        this.changeSupplierUrl = changeSupplierUrl;
        this.selectSupplierMessage = selectSupplierMessage;
        this.createPOUrl = createPOUrl;
    },
    switchSupplier: function(element){
        new Ajax.Request(this.changeSupplierUrl, {
            method: "post",
            parameters: {supplier_id: element.value},
            onComplete: function (transport) {
                this.grid.doFilter();
            }.bind(this)
        });        
    },
    createPO: function(){
        if($(this.supplierFilter).value == '0'){
            alert(this.selectSupplierMessage);
            $(this.supplierFilter).style = 'border-color: #ff0000;';
        } else {
            location.href = this.createPOUrl;
        }
    }
}