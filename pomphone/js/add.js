var count = 0,itemcount = 0,sumpricebill=0;
var dataImei = [],result = [];

$(document).ready(function(){  
    $('select.number').on('itemAdded', function(event) {   //  เพิ่ม IEMI
        dataImei[itemcount] = $("select").val();
        count++;
        document.getElementById("numbercount").value = count;
    });
    $('select.number').on('itemRemoved', function(event) { //  เพิ่ม IEMI
        count--;
        document.getElementById("numbercount").value = count;
    });


});
function deletAll(){
    count=0;
        document.getElementById("numbercount").value = count;
    $('select.number').tagsinput('removeAll');
}
function addData(){
    if(document.getElementById("nameproduc").value!=""&&document.getElementById("price").value!=""){
        result[itemcount] = [document.getElementById("nameproduc").value,document.getElementById("price").value,dataImei[itemcount]];
        AddtoTable();deletAll();
        document.getElementById("price").value = null;
        document.getElementById("nameproduc").value = null;
        itemcount++;
    }else{
        alert("โปรดข้อมูลให้ครบ ");
    }
}

function AddtoTable(){
    var table = document.getElementById("MytableData");
    var row = table.insertRow(-1);
    var cell1 = row.insertCell(0);
    var cell2 = row.insertCell(1);
    var cell3 = row.insertCell(2);
    var cell4 = row.insertCell(3);
    var cell5 = row.insertCell(4);
    var cell6 = row.insertCell(5);
    cell1.innerHTML = String(itemcount+1); // อันดับ
    cell2.innerHTML = String(result[itemcount][0]); // ชื่อสินค้า
    cell3.innerHTML = String(dataImei[itemcount].length); // จำนวน
    cell4.innerHTML = String(result[itemcount][1]); // ราคา
    cell5.innerHTML = String(result[itemcount][2]); //Imei
    cell6.innerHTML = String(dataImei[itemcount].length*result[itemcount][1]); //ราคารวม
    sumpricebill+=dataImei[itemcount].length*result[itemcount][1];
    document.getElementById("sum").innerHTML = sumpricebill;
}
function showdata(){
    if(result && result.length > 0){
        console.log("Have Data");
    }else{
        console.log("Not Data");
    }
    console.log("=========SHOW=============");
    console.log("Itemcount: "+itemcount);
    console.log(result[itemcount-1][0]);
    console.log("=======================");
    console.log(result);
}
