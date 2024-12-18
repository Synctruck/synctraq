import React, { useState, useEffect } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'

function Driver() {

    const [id, setId]                   = useState(0);
    const [idRole, setIdRole]           = useState(4);
    const [name, setName]               = useState('');
    const [nameOfOwner, setNameOfOwner] = useState('');
    const [address, setAddress]         = useState('');
    const [phone, setPhone]             = useState('');
    const [email, setEmail]             = useState('');
    const [usageApp, setUsageApp]       = useState('Onfleet');
    const [status, setStatus]           = useState('');
    const [roleUser, setRoleUser]       = useState([]);
    const [idTeam, setIdTeam]           = useState(0);
    const [idOnfleet, setIdOnfleet]     = useState('');

    const [disabledButton, setDisabledButton] = useState(false);

    const [listUser, setListUser] = useState([]);
    const [listRole, setListRole] = useState([]);
    const [listTeam, setListTeam] = useState([]); 

    const [page, setPage] = useState(1);
    const [totalPage, setTotalPage] = useState(0);
    const [totalUser, setTotalUser] = useState(0);

    const [titleModal, setTitleModal] = useState('');

    const [textSearch, setSearch] = useState('');
    const [textButtonSave, setTextButtonSave] = useState('Guardar');

    useEffect(() => {
 
        listAllUser(page);

    }, [textSearch])

    const handlerChangePage = (pageNumber) => {

        listAllUser(pageNumber);
    }

    const listAllUser = (pageNumber) => {

        fetch(url_general +'driver/list?page='+ pageNumber +'&textSearch='+ textSearch)
        .then(res => res.json())
        .then((response) => {

            setListUser(response.userList.data);
            setPage(response.userList.current_page);
            setTotalPage(response.userList.per_page);
            setTotalUser(response.userList.total);
            setRoleUser(response.roleUser);

            listAllTeam();

            /*if(response.roleUser == 'Master')
            {
                
            }
            else
            {
                listAllDriverByTeam(idUserGeneral);
                setIdTeam(idUserGeneral);
            }*/
        });
    }

    const listAllRole = (pageNumber) => {

        fetch(url_general +'role/list')
        .then(res => res.json())
        .then((response) => {

            setListRole(response.roleList);
        });
    }

    const listAllTeam = () => {

        fetch(url_general +'team/listall')
        .then(res => res.json())
        .then((response) => {

            setListTeam(response.listTeam);
        });
    }

    const handlerOpenModal = (id) => {

        clearValidation();

        if(id)
        {
            setTitleModal('Update Driver')
            setTextButtonSave('Update');
        }
        else
        {
            listAllRole();
            clearForm();
            setTitleModal('Add Driver');
            setTextButtonSave('Save');
        }

        let myModal = new bootstrap.Modal(document.getElementById('modalDriverInsert'), {

            keyboard: true
        });

        myModal.show();
    }

    const handlerSaveUser = (e) => {

        e.preventDefault();

        const formData = new FormData();

        formData.append('idRole', idRole);
        formData.append('idTeam', idTeam);
        formData.append('name', name);
        formData.append('nameOfOwner', nameOfOwner);
        formData.append('address', address);
        formData.append('phone', phone);
        formData.append('email', email);
        formData.append('usageApp', usageApp);
        formData.append('status', status);

        clearValidation();

        if(id == 0)
        {
            let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            setDisabledButton(true);

            fetch(url_general +'driver/insert', {
                headers: { "X-CSRF-TOKEN": token },
                method: 'post',
                body: formData
            })
            .then(res => res.json()).
            then((response) => {

                    if(response.stateAction == true)
                    {
                        swal("Driver was registered!", {

                            icon: "success",
                        });

                        listAllUser(1);
                        clearForm();
                    }
                    else if(response.stateAction == 'phoneIncorrect')
                    {
                        swal("The phone number of the driver is wrong!", {

                            icon: "warning",
                        });
                    }
                    else if(response.stateAction == 'notRegisterPODApp')
                    {
                        swal('PODApp Message: '+ response.response.error, {

                            icon: "warning",
                        });
                    }
                    else(response.status == 422)
                    {
                        for(const index in response.errors)
                        {
                            document.getElementById(index).style.display = 'block';
                            document.getElementById(index).innerHTML     = response.errors[index][0];
                        }
                    }

                    setDisabledButton(false);
                },
            );
        }
        else
        {
            setDisabledButton(true);

            let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            fetch(url_general +'driver/update/'+ id, {
                headers: {
                    "X-CSRF-TOKEN": token
                },
                method: 'post',
                body: formData
            })
            .then(res => res.json()).
            then((response) => {

                if(response.stateAction == 'phoneNotExists')
                {
                    listAllUser(1);

                    swal("The phone of the Driver does not exist in onfleet!", {

                        icon: "warning",
                    });
                }
                else if(response.stateAction == 'userPackageDispatch')
                {
                    swal("The user cannot change Team because he has packages in dispatch", {

                        icon: "warning",
                    });
                }
                else if(response.stateAction == true)
                {
                    listAllUser(1);

                    swal("Driver was updated!", {

                        icon: "success",
                    });
                }
                else(response.status == 422)
                {
                    for(const index in response.errors)
                    {
                        document.getElementById(index).style.display = 'block';
                        document.getElementById(index).innerHTML     = response.errors[index][0];
                    }
                }

                setDisabledButton(false);
            });
        }
    }

    const getUser = (id) => {

        LoadingShow();

        listAllRole();

        fetch(url_general +'driver/get/'+ id)
        .then(response => response.json())
        .then(response => {

            let driver = response.driver;

            setId(driver.id);
            setIdRole(driver.idRole);
            setName(driver.name);
            setNameOfOwner(driver.nameOfOwner);
            setAddress(driver.address);
            setPhone(driver.phone);
            setEmail(driver.email);
            setIdOnfleet(driver.idOnfleet);
            setIdTeam(driver.idTeam);
            setStatus(driver.status);

            listAllTeam();
            handlerOpenModal(driver.id);
            LoadingHide();
        });
    }

    const changeStatus = (id) => {

        swal({
            title: "You want to change the status of the Driver?",
            text: "Change state!",
            icon: "warning",
            buttons: true,
            dangerMode: true,
        })
        .then((willDelete) => {

            if(willDelete)
            {
                LoadingShow();

                fetch(url_general +'driver/changeStatus/'+ id)
                .then(response => response.json())
                .then(response => {

                    if(response.stateAction)
                    {
                        swal("Driver status changed!", {

                            icon: "success",
                        });

                        listAllUser(page);
                    }

                    LoadingHide();
                });
            }
        });
    }

    const deleteUser = (id) => {

        swal({
            title: "You want to delete?",
            text: "Driver will be removed!",
            icon: "warning",
            buttons: true,
            dangerMode: true,
        })
        .then((willDelete) => {

            if(willDelete)
            {
                LoadingShow();

                fetch(url_general +'driver/delete/'+ id)
                .then(response => response.json())
                .then(response => {

                    if(response.stateAction)
                    {
                        swal("Driver successfully removed!", {

                            icon: "success",
                        });

                        listAllUser(page);

                        LoadingHide();
                    }
                });
            }
        });
    }

    const clearForm = () => {

        setId(0);
        setIdRole(0);
        setName('');
        setNameOfOwner('');
        setAddress('');
        setPhone('');
        setEmail('');
        setStatus('Active');
        setUsageApp('Onfleet');
    }

    const clearValidation = () => {

        document.getElementById('idRole').style.display = 'none';
        document.getElementById('idRole').innerHTML     = '';

        document.getElementById('name').style.display = 'none';
        document.getElementById('name').innerHTML     = '';

        document.getElementById('nameOfOwner').style.display = 'none';
        document.getElementById('nameOfOwner').innerHTML     = '';

        document.getElementById('phone').style.display = 'none';
        document.getElementById('phone').innerHTML     = '';

        document.getElementById('email').style.display = 'none';
        document.getElementById('email').innerHTML     = '';

        document.getElementById('usageApp').style.display = 'none';
        document.getElementById('usageApp').innerHTML     = '';

        document.getElementById('status').style.display = 'none';
        document.getElementById('status').innerHTML     = '';
    }

    const synchronize = async (id) => {

        LoadingShowMap();

        await fetch(url_general +'driver/synchronize/'+ id)
        .then(response => response.json())
        .then(response => {

            if(response.statusCode == true)
            {
                swal("Driver data was synchronized correctly!", {

                    icon: "success",
                });

                listAllUser(page);
            }
            else
            {
                swal("There was an error try again!", {

                    icon: "error",
                });
            }

            LoadingHideMap();
        });
    }

    const listDriverTable = listUser.map( (user, i) => {

        return (

            <tr key={i}>
                <td><b>{ user.nameTeam }</b></td>
                <td>{ user.name +' '+ user.nameOfOwner }</td>
                <td>{ user.phone }</td> 
                <td>{ user.email }</td>
                <td>{ user.idOnfleet }</td>
                <td>{ user.usageApp }</td>
                <td>
                    {
                        (
                            user.status == 'Active'
                            ?
                                <button className="alert alert-success font-weight-bold" onClick={ () => changeStatus(user.id) }>{ user.status }</button>
                            :
                                <button className="alert alert-danger font-weight-bold" onClick={ () => changeStatus(user.id) }>{ user.status }</button>
                        )
                    }
                </td>
                <td>
                    <button className="btn btn-primary btn-sm m-1" title="Edit" onClick={ () => getUser(user.id) }>
                        <i className="bx bx-edit-alt"></i>
                    </button>
                    
                    {
                        (
                            user.deleteUser == 0
                            ?
                                <div>
                                    <button className="btn btn-danger btn-sm m-1" title="Delete" onClick={ () => deleteUser(user.id) }>
                                        <i className="bx bxs-trash-alt"></i>
                                    </button>
                                </div>
                            :
                                ''
                        )
                    }

                    {
                        (
                            user.registerNewSystem == 0
                            ?
                                <div>
                                    <button className="btn btn-success btn-sm" title="Synchronize with new system" onClick={ () => synchronize(user.id) }>
                                        <i className="bx bxs-user-plus"></i>
                                    </button>
                                </div>
                            :
                                ''
                        )
                    }
                </td>
            </tr>
        );
    });

    const listRoleSelect = listRole.map( (role, i) => {

        return (

            (
                role.name == 'Driver'
                ?
                    <option value={ role.id }>{ role.name }</option>
                :
                    ''
            )

        );
    });

    const listTeamSelect = listTeam.map( (team, i) => {

        return (

            <option value={ team.id } selected={ team.id == idTeam ? true : false }>{ team.name }</option>
        );
    });

    const handlerResetPassword = (emailUser) => {

        swal({
            title: "You want to reset password?",
            text: "Password will be reset!",
            icon: "warning",
            buttons: true,
            dangerMode: true,
        })
        .then((willDelete) => {

            if(willDelete)
            {
                fetch(url_general +'user/resetPassword/'+ emailUser)
                .then(response => response.json())
                .then(response => {

                    if(response.stateAction == true)
                    {
                        swal("Password reset successfully!", {

                            icon: "success",
                        });
                    }
                    else if(response.stateAction == false)
                    {
                        swal(response.message, {

                            icon: "warning",
                        });
                    }
                });
            }
        });
    }
    
    const modalDriverInsert = <React.Fragment>
                                    <div className="modal fade" id="modalDriverInsert" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div className="modal-dialog">
                                            <form onSubmit={ handlerSaveUser }>
                                                <div className="modal-content">
                                                    <div className="modal-header">
                                                        <h5 className="modal-title text-primary" id="exampleModalLabel">{ titleModal }</h5>
                                                        <button type="button" className="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div className="modal-body">
                                                        <div className="row">
                                                            <div className="col-lg-6">
                                                                <div className="form-group">
                                                                    <a href="#" className="text-danger" onClick={ () => handlerResetPassword(email)  }><b><u>Reset Password</u></b></a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div className="row">
                                                            <div className="col-lg-12">
                                                                <div className="form-group">
                                                                    <label className="form">Role</label>
                                                                    <div id="idRole" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <select value={ idRole } className="form-control" onChange={ (e) => setIdRole(e.target.value) } required>
                                                                        { listRoleSelect }
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div className="col-lg-12">
                                                            <div className="form-group">
                                                                <label className="form">TEAM</label>
                                                                <select name="" id="" className="form-control" onChange={ (e) => setIdTeam(e.target.value) } required>
                                                                    <option value="" style={ {display: 'none'} }>Select a team</option>
                                                                    { listTeamSelect }
                                                                </select>
                                                            </div>
                                                        </div>

                                                        <div className="row">
                                                            <div className="col-lg-6">
                                                                <div className="form-group">
                                                                    <label className="form">First Name</label>
                                                                    <div id="name" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="text" value={ name } className="form-control" onChange={ (e) => setName(e.target.value) } required/>
                                                                </div>
                                                            </div>
                                                            <div className="col-lg-6">
                                                                <div className="form-group">
                                                                    <label className="form">Last Name</label>
                                                                    <div id="nameOfOwner" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="text" value={ nameOfOwner } className="form-control" onChange={ (e) => setNameOfOwner(e.target.value) } required/>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div className="row">
                                                            <div className="col-lg-6">
                                                                <div className="form-group">
                                                                    <label className="form">Phone</label>
                                                                    <div id="phone" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="text" value={ phone } className="form-control" onChange={ (e) => setPhone(e.target.value) }/>
                                                                </div>
                                                            </div>
                                                            <div className="col-lg-6">
                                                                <div className="form-group">
                                                                    <label className="form">Email</label>
                                                                    <div id="email" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="email" value={ email } className="form-control" onChange={ (e) => setEmail(e.target.value) } required/>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div className="row">
                                                            <div className="col-lg-12">
                                                                <div className="form-group">
                                                                    <label className="form">USAGE APP</label>
                                                                    <div id="usageApp" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <select value={ usageApp } className="form-control" onChange={ (e) => setUsageApp(e.target.value) } required>
                                                                        <option value="Onfleet" >Onfleet</option>
                                                                        <option value="PODApp" >PODApp</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div className="row">
                                                            <div className="col-lg-6">
                                                                <div className="form-group">
                                                                    <label className="form">Id Onfleet</label>
                                                                    <input type="text" value={ idOnfleet } className="form-control" readOnly/>
                                                                </div>
                                                            </div>

                                                            <div className="col-lg-6">
                                                                <div className="form-group">
                                                                    <label className="form">Status</label>
                                                                    <div id="status" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <select value={ status } className="form-control" onChange={ (e) => setStatus(e.target.value) } required>
                                                                        <option value="Active" >Active</option>
                                                                        <option value="Inactive" >Inactive</option>
                                                                    </select>
                                                                </div>
                                                            </div>

                                                        </div>
                                                    </div>
                                                    <div className="modal-footer">
                                                        <button type="button" className="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        <button className="btn btn-primary" disabled={ disabledButton }>{ textButtonSave }</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </React.Fragment>;

    return (

        <section className="section">
            { modalDriverInsert }
            <div className="row">
                <div className="col-lg-12">
                    <div className="card">
                        <div className="card-body">
                            <h5 className="card-title">
                                <div className="row form-group">
                                    <div className="col-lg-2">
                                        <button className="btn btn-success form-control" title="Agregar" onClick={ () => handlerOpenModal(0) }>
                                            <i className="bx bxs-plus-square"></i> Add
                                        </button>
                                    </div>
                                </div>
                            </h5>
                            <div className="row form-group">
                                <div className="col-lg-12">
                                    <input type="text" value={textSearch} onChange={ (e) => setSearch(e.target.value) } className="form-control" placeholder="Buscar..."/>
                                    <br/>
                                </div>
                            </div>
                            <div className="row form-group">
                                <div className="col-lg-12">
                                    <table className="table table-hover table-condensed">
                                        <thead>
                                            <tr>
                                                <th>TEAM</th>
                                                <th>FULL NAME DRIVER</th>
                                                <th>PHONE</th>
                                                <th>EMAIL</th>
                                                <th>ID ONFLEET</th>
                                                <th>USAGE APP</th>
                                                <th>STATUS</th>
                                                <th>ACTIONS</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            { listDriverTable }
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div className="col-lg-12">
                    <Pagination
                        activePage={page}
                        totalItemsCount={totalUser}
                        itemsCountPerPage={totalPage}
                        onChange={(pageNumber) => handlerChangePage(pageNumber)}
                        itemClass="page-item"
                        linkClass="page-link"
                        firstPageText="First"
                        lastPageText="Last"
                    />
                </div>
            </div>
        </section>
    );
}

export default Driver;

// DOM element
if (document.getElementById('driver')) {
    ReactDOM.render(<Driver />, document.getElementById('driver'));
}
