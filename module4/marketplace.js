function redeemItem(rewardID, rewardName){
    if(!confirm(`Are you sure you want to redeem ${rewardName}?`)) return;

    const formData = new FormData();
    formData.append('reward_id',rewardID);
    fetch('Process-redeem.php',{
        method:'POST',
        body:formData
    })
    .then(response => response.json())
    .then(data => {
       if(data.success){
        alert("Success!" + data.message);
        location.reload();//referesh page, update points and stock.
       } else {
        alert("Error: " + data.message);
       }
    })
    .catch(error =>{
        console.error('Error:',error);
        alert("An unexpected error occurred.");
    });
}