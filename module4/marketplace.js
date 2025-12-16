function redeemItem(rewardID, rewardName){
    // Replace confirm
    Swal.fire({
        title: 'Redeem this reward?',
        text: `Are you sure you want to spend points on ${rewardName}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981', // Emerald-500
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, redeem it!'
    }).then((result) => {
        if (result.isConfirmed) {
            // User clicked Yes, send request
            const formData = new FormData();
            formData.append('reward_id', rewardID);

            fetch('Process-redeem.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.success){
                    // Replace success alert
                    Swal.fire({
                        title: 'Success!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonColor: '#10b981'
                    }).then(() => {
                        location.reload(); 
                    });
                } else {
                    // Replace failure alert
                    Swal.fire('Oops...', data.message, 'error');
                }
            })
            .catch(error => {
                Swal.fire('Error', 'An unexpected error occurred.', 'error');
            });
        }
    });
}
document.addEventListener('DOMContentLoaded', function() {
    // 1. Get elements
    const searchInput = document.getElementById('searchReward');
    const typeFilter = document.getElementById('typeFilter');
    const priceFilter = document.getElementById('priceFilter');
    const cards = document.querySelectorAll('.reward-card');

    // 2. Define core filter function
    function filterRewards() {
        const searchValue = searchInput.value.toLowerCase();
        const typeValue = typeFilter.value;
        const priceValue = priceFilter.value;

        cards.forEach(card => {
            // Get data from card
            const name = card.getAttribute('data-name'); // already lowercase
            const type = card.getAttribute('data-type');
            const points = parseInt(card.getAttribute('data-points'));

            // A. Match search (contains keyword)
            const matchSearch = name.includes(searchValue);

            // B. Match type (if 'all' or exact type match)
            const matchType = (typeValue === 'all' || type === typeValue);

            // C. 匹配价格范围
            let matchPrice = true;
            if (priceValue === 'affordable') matchPrice = points < 500;
            else if (priceValue === 'medium') matchPrice = points >= 500 && points <= 1000;
            else if (priceValue === 'premium') matchPrice = points > 1000;

            // 3. 最终决定：显示还是隐藏
            if (matchSearch && matchType && matchPrice) {
                card.style.display = ''; // 恢复默认显示 (flex/block)
            } else {
                card.style.display = 'none'; // 隐藏
            }
        });
    }

    // 3. 绑定事件 (输入或选择改变时触发)
    searchInput.addEventListener('input', filterRewards);
    typeFilter.addEventListener('change', filterRewards);
    priceFilter.addEventListener('change', filterRewards);
});