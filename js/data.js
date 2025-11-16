

$('#formInfo').on('submit', function (e){
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('ajax', true);

    axios.post('../php/insert_data.php', formData)
      .then(res => {
        if (res.data.success) {
          Swal.fire({
            icon: 'success',
            title: 'Succesfully Saved',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000
          }).then(() => {
            window.location.replace("../sections/test.html");
          });
          
        }else {
          alert("Hubo un error");
        }

        });
});
