import React, { useState, useEffect } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'
import Select from 'react-select'
import axios from 'axios';
import { constant } from 'lodash'


function UserProfile() {

    const [oldPassword , setOldPassword] = useState('');
    const [newPassword , setNewPassword] = useState('');
    const [confirmationPassword , setConfirmationPassword] = useState('');

    const [messageOldPassword , setMessageOldPassword]                   = useState('');
    const [messageNewPassword , setMessageNewPassword]                   = useState('');
    const [messageConfirmationPassword , setMessageConfirmationPassword] = useState('');

    const [name , setName] = useState('');
    const [nameOfOwner , setNameOfOwner] = useState('');
    const [roleName , setRoleName] = useState('');
    const [phone , setPhone] = useState('');
    const [email , setEmail] = useState('');
    const [address , setAddress] = useState('');
    const [teamName , setTeamName] = useState('');
    const [urlImage , setUrlImage] = useState('');
    const [selectedFile, setSelectedFile] = useState('');

    const [permissionsList, setPermissionsList] = useState([]);
    const [rolePermissions, setRolePermissions] = useState([]);

    useEffect(() => {
        getUserData();
    }, [])



    const clearValidation = () => {

        document.getElementById('Reference_Number_1_Edit').style.display = 'none';
        document.getElementById('Reference_Number_1_Edit').innerHTML     = '';

        document.getElementById('Dropoff_Contact_Name').style.display = 'none';
        document.getElementById('Dropoff_Contact_Name').innerHTML     = '';

        document.getElementById('Dropoff_Contact_Phone_Number').style.display = 'none';
        document.getElementById('Dropoff_Contact_Phone_Number').innerHTML     = '';
    }


    const clearForm = () => {


    }
    const handleCheckPermissions =(event) => {
        console.log(event);
    }

    const renderListPermission = permissionsList.map((item,index)=>{

        if(item.slug == 'T'){
            return <span><b>{item.name}</b> </span>;
        }
        if(item.slug!='T' && item.parent_id !=null){
            return <div class="checkbox checkbox-primary mb-1" style={{paddingLeft:'30px'}} >
                        <input
                            className="form-check-input"
                            type="checkbox"
                            value={item.id}
                            id={item.id+'ck'}
                            checked={rolePermissions.includes(item.id)}
                            onChange={handleCheckPermissions}
                            //  disabled= {(id == 1)?true:false}
                        />  <label htmlFor={item.id+'ck'}> { item.name }</label>
                    </div>;
        }
        if(item.slug!='T' && item.parent_id ==null){
            return  <div class="checkbox checkbox-primary mt-1  mb-1" style={{paddingLeft:'10px'}}>
            <input
                className="form-check-input"
                type="checkbox"
                value={item.id}
                id={item.id+'ck'}
                checked={rolePermissions.includes(item.id)}
                onChange={handleCheckPermissions}
                // disabled= {(id == 1)?true:false}
            />  <label htmlFor={item.id+'ck'} style={{fontWeight:'500'}}>  { item.name }</label>
        </div>
        }

    });

    const getUserData = () => {

        let url = url_general +'getProfile'
        let method = 'GET'

        axios({
            method: method,
            url: url
        })
        .then((response) => {
           console.log(response.data);
           let dataUser = response.data.user;
           setName(dataUser.name);
           setNameOfOwner(dataUser.nameOfOwner);
           setRoleName(dataUser.role.name);
           setPhone(dataUser.phone);
           setEmail(dataUser.email);
           setAddress(dataUser.address);
           setTeamName(dataUser.nameTeam);
           setUrlImage(dataUser.url_image);

           setPermissionsList(response.data.allPermissions);
           let permissions = []
            dataUser.role.permissions.map(function(element) {
                permissions.push(element.id)
            });

            setRolePermissions(permissions);

            console.log(permissionsList,rolePermissions);
        })
        .catch(function(error) {
            let errors = error.response.data.errors
            if(error.response.status == 422){
                for(const index in errors)
                {
                    document.getElementById(index).style.display = 'block';
                    document.getElementById(index).innerHTML     = errors[index][0];
                }
            }
        })
        .finally(() => LoadingHide());
    }

    const handlerSavePassword = (e) => {

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

    const handlerSave = (e) => {

        e.preventDefault();


        let url = url_general +'profile'
        let formData = new FormData();

        formData.append("name", name);
        formData.append("nameOfOwner", nameOfOwner);
        formData.append("address", address);
        formData.append("image", selectedFile);


        axios.defaults.headers.common['Content-Type'] = 'multipart/form-data';
        axios.post(url,formData)
        .then(response => {
            swal("User was recorded!", {
                icon: "success",
            });

            setSelectedFile('');
            var inputImage = document.getElementById("imageUser");
            inputImage.value = '';
            setUrlImage(response.data.user.url_image);
        })
        .catch(error => {
            let errors = error.response.data.errors
            if(error.response.status == 422){
                for(const index in errors)
                {
                    document.getElementById(index).style.display = 'block';
                    document.getElementById(index).innerHTML     = errors[index][0];
                }
            }
        })
        .finally(() => LoadingHide());
    }

    return (

    <section className="section profile">
        <div className="row">
          <div className="col-xl-4">

            <div className="card">
              <div className="card-body profile-card pt-4 d-flex flex-column align-items-center">

                <img src={urlImage} alt="Profile" className="rounded-circle" />
                <h2>{name}  {nameOfOwner}</h2>
                <h3>{roleName}</h3>

              </div>
            </div>

          </div>

            <div className="col-xl-8">

                <div className="card">
                    <div className="card-body pt-3">
                    <ul className="nav nav-tabs nav-tabs-bordered">



                    <li className="nav-item">
                        <button className="nav-link active" data-bs-toggle="tab" data-bs-target="#profile-edit">Edit Profile</button>
                    </li>

                    <li className="nav-item">
                        <button className="nav-link" data-bs-toggle="tab" data-bs-target="#profile-settings">Permissions</button>
                    </li>

                    <li className="nav-item">
                        <button className="nav-link" data-bs-toggle="tab" data-bs-target="#profile-change-password">Change Password</button>
                    </li>

                    </ul>
                    <div className="tab-content pt-2">

                    <div className="tab-pane fade profile-edit pt-3 show active"  id="profile-edit">

                        <form onSubmit={ handlerSave } >
                        <div className="row mb-3">
                            <label htmlFor="profileImage" className="col-md-4 col-lg-3 col-form-label">Profile Image <small>optional</small></label>
                            <div className="col-md-8 col-lg-9">
                                <input type="file" id='imageUser' className='form-control' accept="image/png, image/jpg, image/jpeg"  onChange={(e) => setSelectedFile(e.target.files[0])}/>
                            </div>
                        </div>

                        <div className="row mb-3">
                            <label htmlFor="firstName" className="col-md-4 col-lg-3 col-form-label">First name</label>
                            <div className="col-md-8 col-lg-9">
                            <div id="name" className="text-danger" style={ {display: 'none'} }></div>
                            <input name="firstName" type="text" className="form-control" id="firstName" value={name} onChange={ (e) => setName(e.target.value) }  required/>
                            </div>
                        </div>
                        <div className="row mb-3">
                            <label htmlFor="lastName" className="col-md-4 col-lg-3 col-form-label">Last name</label>
                            <div className="col-md-8 col-lg-9">
                            <div id="nameOfOwner" className="text-danger" style={ {display: 'none'} }></div>
                            <input name="lastName" type="text" className="form-control" id="lastName" value={nameOfOwner} onChange={ (e) => setNameOfOwner(e.target.value) }  required/>
                            </div>
                        </div>

                        <div className="row mb-3">
                            <label htmlFor="team" className="col-md-4 col-lg-3 col-form-label">Team</label>
                            <div className="col-md-8 col-lg-9">

                            <input name="team" type="text" className="form-control" id="team" value={teamName}  disabled/>
                            </div>
                        </div>

                        <div className="row mb-3">
                            <label htmlFor="roleName" className="col-md-4 col-lg-3 col-form-label">role</label>
                            <div className="col-md-8 col-lg-9">
                            <input name="roleName" type="text" className="form-control" id="roleName" value={roleName} disabled/>
                            </div>
                        </div>

                        <div className="row mb-3">
                            <label htmlFor="Address" className="col-md-4 col-lg-3 col-form-label">Address</label>
                            <div className="col-md-8 col-lg-9">
                            <div id="address" className="text-danger" style={ {display: 'none'} }></div>
                            <input name="address" type="text" className="form-control"  value={address} onChange={ (e) => setAddress(e.target.value) }  />
                            </div>
                        </div>

                        <div className="row mb-3">
                            <label htmlFor="Phone" className="col-md-4 col-lg-3 col-form-label">Phone</label>
                            <div className="col-md-8 col-lg-9">
                            <input name="phone" type="text" className="form-control" id="Phone" value={phone} disabled />
                            </div>
                        </div>

                        <div className="row mb-3">
                            <label htmlFor="Email" className="col-md-4 col-lg-3 col-form-label">Email</label>
                            <div className="col-md-8 col-lg-9">
                            <input name="email" type="email" className="form-control" id="Email" value={email} disabled />
                            </div>
                        </div>



                        <div className="text-center">
                            <button type="submit" className="btn btn-primary">Save Changes</button>
                        </div>
                        </form>
                    </div>

                    <div className="tab-pane fade pt-3" id="profile-settings">

                        <div className="row mb-3">
                            <label htmlFor="fullName" className="col-md-4 col-lg-3 col-form-label">My permissions</label>
                        </div>
                            <div class="row">
                                <div class="col-md-6">
                                   {renderListPermission}
                                </div>
                            </div>
                    </div>

                    <div className="tab-pane fade pt-3" id="profile-change-password">
                        <form onSubmit={ handlerSavePassword } autoComplete="off">
                            <div className="row mb-3">
                                <label htmlFor="currentPassword" className="col-md-4 col-lg-3 col-form-label">Current Password</label>
                                <div className="col-md-8 col-lg-9">
                                <div id="oldPassword" className="text-danger">{ messageOldPassword }</div>
                                <input type="text" className="form-control" value={ oldPassword } onChange={ (e) => setOldPassword(e.target.value) } maxLength="30" required/>
                                </div>
                            </div>

                            <div className="row mb-3">
                                <label htmlFor="newPassword" className="col-md-4 col-lg-3 col-form-label">New Password</label>
                                <div className="col-md-8 col-lg-9">
                                <div id="newPassword" className="text-danger">{ messageNewPassword }</div>
                                <input type="password" className="form-control" value={ newPassword } onChange={ (e) => setNewPassword(e.target.value) } maxLength="30" required/>
                                </div>
                            </div>

                            <div className="row mb-3">
                                <label htmlFor="renewPassword" className="col-md-4 col-lg-3 col-form-label">Re-enter New Password</label>
                                <div className="col-md-8 col-lg-9">
                                <div id="idRole" className="text-danger">{ messageConfirmationPassword }</div>
                                <input type="password" className="form-control" value={ confirmationPassword } onChange={ (e) => setConfirmationPassword(e.target.value) } maxLength="30" required/>
                                </div>
                            </div>

                            <div className="text-center">
                                <button type="submit" className="btn btn-primary">Change Password</button>
                            </div>
                        </form>
                    </div>
                </div>
                    </div>
                </div>
            </div>
        </div>
      </section>
    );
}

export default UserProfile;

// DOM element
if (document.getElementById('userProfile')) {
    ReactDOM.render(<UserProfile />, document.getElementById('userProfile'));
}
