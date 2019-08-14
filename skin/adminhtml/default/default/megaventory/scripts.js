var MegaventoryManager={

    changeCountsInStock : function(inventoryId , oneOrZero, url){

        new Ajax.Request(url, {

            method:'post',

            parameters : {

                inventoryId:inventoryId,

                value:oneOrZero

            },

            onFailure: function() {

                alert('An error occurred while saving the data.');

            },

            onSuccess : function(response){

            }



        });

    },

	redo : function(url, logid){

    new Ajax.Request(url, {

        method:'post',

        parameters : {
            logId:logid
        },
        onFailure: function() {
            alert('An error occurred while saving the data.');
        },

        onSuccess : function(response){
        	location.reload(true);
        	}
    	});

	},
    
    synchronizeOrder : function(url, orderid){

        new Ajax.Request(url, {

            method:'post',

            parameters : {
                orderId:orderid
            },
            onFailure: function() {
                alert('An error occurred while synchronizing order.');
            },

            onSuccess : function(response){
            	location.reload(true);
            	}
        	});

    }
}
