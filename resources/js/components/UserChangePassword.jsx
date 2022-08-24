import React, { useState, useEffect } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'
import Select from 'react-select'


function UserChangePassword() {

    const [oldPassword , setOldPassword] = useState('');
    const [newPassword , setNewPassword] = useState('');
    const [confirmationPassword , setConfirmationPassword] = useState('');

    const [messageOldPassword , setMessageOldPassword]                   = useState('');
    const [messageNewPassword , setMessageNewPassword]                   = useState('');
    const [messageConfirmationPassword , setMessageConfirmationPassword] = useState('');

    const clearValidation = () => {

        document.getElementById('Reference_Number_1_Edit').style.display = 'none';
        document.getElementById('Reference_Number_1_Edit').innerHTML     = '';

        document.getElementById('Dropoff_Contact_Name').style.display = 'none';
        document.getElementById('Dropoff_Contact_Name').innerHTML     = '';

        document.getElementById('Dropoff_Contact_Phone_Number').style.display = 'none';
        document.getElementById('Dropoff_Contact_Phone_Number').innerHTML     = '';
    }

    const clearForm = () => {

        setReference_Number_1('');
        setDropoff_Contact_Name('');
        setDropoff_Contact_Phone_Number('');
        setDropoff_Address_Line_1('');
        setDropoff_Address_Line_2('');
        setDropoff_City('');
        setDropoff_Province('');
        setDropoff_Postal_Code('');
        setWeight('');
        setRoute(0);
    }

    const handlerSave = (e) => {

        e.preventDefault();

        LoadingShow();

        const formData = new FormData();

        formData.append('oldPassword', oldPassword);
        formData.append('newPassword', newPassword);
        formData.append('confirmationPassword', confirmationPassword);

        let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        fetch(url_general +'user/changePassword/save', {
            headers: { "X-CSRF-TOKEN": token },
            method: 'post',
            body: formData
        })
        .then(res => res.json())
        .then((response) => {

                if(response.stateAction == 'error-passwordOld')
                {
                    setMessageOldPassword('Wrong current password');
                    setMessageNewPassword('');
                    setMessageConfirmationPassword('');
                }
                else if(response.stateAction == 'error-passwordConfirm')
                {
                    setMessageOldPassword('');
                    setMessageNewPassword('These fields have to be the same');
                    setMessageConfirmationPassword('These fields have to be the same');
                }
                else if(response.stateAction == true)
                {
                    setMessageOldPassword('');
                    setMessageNewPassword('');
                    setMessageConfirmationPassword('');

                    swal('Correct!', 'Updated password', 'success');
                }
                else if(response.status == 422)
                {
                    swal('Attention!', 'All fields are required', 'error');
                }

                LoadingHide();
            },
        )
        ;
    }

    return (

        <section className="section">
            <div className="row">
                <div className="col-lg-12">
                    <div className="card">
                        <div className="card-body">
                            <h5 className="card-title">
                                <form onSubmit={ handlerSave } autoComplete="off">
                                    <div className="row">
                                        <div className="col-lg-3">
                                            <div className="form-group">
                                                <label htmlFor="">OLD PASSWORD</label>
                                                <div id="oldPassword" className="text-danger">{ messageOldPassword }</div>
                                                <input type="text" className="form-control" value={ oldPassword } onChange={ (e) => setOldPassword(e.target.value) } maxLength="30" required/>
                                            </div>
                                        </div>
                                        <div className="col-lg-3">
                                            <div className="form-group">
                                                <label htmlFor="">NEW PASSWORD</label>
                                                <div id="newPassword" className="text-danger">{ messageNewPassword }</div>
                                                <input type="password" className="form-control" value={ newPassword } onChange={ (e) => setNewPassword(e.target.value) } maxLength="30" required/>
                                            </div>
                                        </div>
                                        <div className="col-lg-3">
                                            <div className="form-group">
                                                <label htmlFor="">CONFIRMATION PASSWORD</label>
                                                <div id="idRole" className="text-danger">{ messageConfirmationPassword }</div>
                                                <input type="password" className="form-control" value={ confirmationPassword } onChange={ (e) => setConfirmationPassword(e.target.value) } maxLength="30" required/>
                                            </div>
                                        </div>
                                        <div className="col-lg-3">
                                            <div className="form-group">
                                                <label htmlFor="" className="text-white">Updated</label><br/>
                                                <button className="btn btn-primary form-control">Update</button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </h5>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}

export default UserChangePassword;

// DOM element
if (document.getElementById('userChangePassword')) {
    ReactDOM.render(<UserChangePassword />, document.getElementById('userChangePassword'));
}
