import React, { useState, useEffect, Component, Fragment } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'
import Select from "react-select";

function Team() {

    const [id, setId]                                 = useState(0);
    const [idRole, setIdRole]                         = useState(0);
    const [name, setName]                             = useState('');
    const [nameOfOwner, setNameOfOwner]               = useState('');
    const [address, setAddress]                       = useState('');
    const [phone, setPhone]                           = useState('');
    const [email, setEmail]                           = useState('');
    const [status, setStatus]                         = useState('');
    const [idsRoutes, setIdsRoutes]                   = useState('');
    const [permissionDispatch, setPermissionDispatch] = useState(0);
    const [idOnfleet, setIdOnfleet]                   = useState('');

    const [disabledButton, setDisabledButton] = useState(false);

    const [listUser, setListUser]   = useState([]);
    const [listRole, setListRole]   = useState([]);
    const [listRoute, setListRoute] = useState([]);
    const [listTeamRoute, setListTeamRoute] = useState([]);

    const [page, setPage] = useState(1);
    const [totalPage, setTotalPage] = useState(0);
    const [totalTeam, setTotalTeam] = useState(0);

    const [titleModal, setTitleModal] = useState('');

    const [textSearch, setSearch] = useState('');
    const [textButtonSave, setTextButtonSave] = useState('Guardar');

    useEffect(() => {

        listAllTeam(page);
        listAllRoute();

    }, [textSearch])

    const handlerChangePage = (pageNumber) => {

        listAllTeam(pageNumber);
    }

    const listAllTeam = (pageNumber) => {

        fetch(url_general +'team/list?page='+ pageNumber +'&textSearch='+ textSearch)
        .then(res => res.json())
        .then((response) => {

            setListUser(response.userList.data);
            setPage(response.userList.current_page);
            setTotalPage(response.userList.per_page);
            setTotalTeam(response.userList.total);
        });
    }

    const listAllRole = (pageNumber) => {

        fetch(url_general +'role/list')
        .then(res => res.json())
        .then((response) => {
            console.log('rolesss: ',response.roleList)
            setListRole(response.roleList);
        });
    }

    const listAllRoute = (pageNumber) => {

        setListRoute([]);

        fetch(url_general +'routes/getAll')
        .then(res => res.json())
        .then((response) => {

            setListRoute(response.routeList);
        });
    }

    const handlerOpenModal = (id) => {

        clearValidation();

        if(id)
        {
            setTitleModal('Update Team')
            setTextButtonSave('Update');
        }
        else
        {
            listAllRole();
            listAllRoute();

            clearForm();
            setTitleModal('Add Team');
            setTextButtonSave('Save');
        }

        let myModal = new bootstrap.Modal(document.getElementById('modalCategoryInsert'), {

            keyboard: true
        });

        myModal.show();
    }

    const handlerSaveTeam = (e) => {

        e.preventDefault();

        const formData = new FormData();

        formData.append('idRole', idRole);
        formData.append('name', name);
        formData.append('nameOfOwner', nameOfOwner);
        formData.append('address', address);
        formData.append('phone', phone);
        formData.append('email', email);
        formData.append('status', status);
        formData.append('routesName', idsRoutes);
        formData.append('permissionDispatch', permissionDispatch);

        clearValidation();

        if(id == 0)
        {
            let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            setDisabledButton(true);

            fetch(url_general +'team/insert', {
                headers: { "X-CSRF-TOKEN": token },
                method: 'post',
                body: formData
            })
            .then(res => res.json()).
            then((response) => {

                    if(response.stateAction == true)
                    {
                        swal("Team was registered!", {

                            icon: "success",
                        });

                        listAllTeam(1);
                        clearForm();
                    }
                    else if(response.stateAction == 'notTeamOnfleet')
                    {
                        swal("The team does not exist in Onfleet!", {

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

            fetch(url_general +'team/update/'+ id, {
                headers: {
                    "X-CSRF-TOKEN": token
                },
                method: 'post',
                body: formData
            })
            .then(res => res.json()).
            then((response) => {

                if(response.stateAction == true)
                {
                    listAllTeam(1);

                    swal("Equipment was updated!", {

                        icon: "success",
                    });
                }
                else if(response.stateAction == 'notTeamOnfleet')
                {
                    swal("The team does not exist in Onfleet!", {

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
            });
        }
    }

    const getTeam = (id) => {

        LoadingShow();

        listAllRole();
        listAllRoute();

        fetch(url_general +'team/get/'+ id)
        .then(response => response.json())
        .then(response => {

            let team = response.team;

            setId(team.id);
            setIdRole(team.idRole);
            setName(team.name);
            setNameOfOwner(team.nameOfOwner);
            setAddress(team.address);
            setPhone(team.phone);
            setEmail(team.email);
            setPermissionDispatch(team.permissionDispatch);
            setStatus(team.status);
            setIdOnfleet(team.idOnfleet);

            setTimeout( () => {

                team.routes_team.forEach( teamRoute => {

                    document.getElementById('idCheck'+ teamRoute.route.name).checked = true;
                });

                handleChange();

            }, 100);

            handlerOpenModal(team.id);

            LoadingHide();
        });
    }

    const changeStatus = (id) => {

        swal({
            title: "You want to change the status of the Team?",
            text: "Change state!",
            icon: "warning",
            buttons: true,
            dangerMode: true,
        })
        .then((willDelete) => {

            if(willDelete)
            {
                LoadingShow();

                fetch(url_general +'team/changeStatus/'+ id)
                .then(response => response.json())
                .then(response => {

                    if(response.stateAction)
                    {
                        swal("Team status changed!", {

                            icon: "success",
                        });

                        listAllTeam(page);
                    }

                    LoadingHide();
                });
            }
        });
    }

    const deleteTeam = (id) => {

        swal({
            title: "You want to delete?",
            text: "Team will be removed!",
            icon: "warning",
            buttons: true,
            dangerMode: true,
        })
        .then((willDelete) => {

            if(willDelete)
            {
                LoadingShow();

                fetch(url_general +'team/delete/'+ id)
                .then(response => response.json())
                .then(response => {

                    if(response.stateAction)
                    {
                        swal("Team was successfully eliminated!", {

                            icon: "success",
                        });

                        listAllTeam(page);
                    }

                    LoadingHide();
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

        document.getElementById('status').style.display = 'none';
        document.getElementById('status').innerHTML     = '';
    }

    const listUserTable = listUser.map( (user, i) => {

        return (

            <tr key={i}>
                <td>
                    <b>{ user.name }</b><br/>
                    { user.nameOfOwner }
                </td>
                <td>{ user.phone }</td>
                <td>{ user.email }</td>
                <td>{ user.idOnfleet }</td> 
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
                    <button className="btn btn-primary btn-sm" title="Editar" onClick={ () => getTeam(user.id) }>
                        <i className="bx bx-edit-alt"></i>
                    </button> &nbsp;

                    <button className="btn btn-danger btn-sm" title="Eliminar" style={{ display: user.drivers.length == 0 ? 'block' : 'none' }} onClick={ () => deleteTeam(user.id) }>
                        <i className="bx bxs-trash-alt"></i>
                    </button>
                </td>
            </tr>
        );
    });

    const listRoleSelect = listRole.map( (role, i) => {
        console.log('roleee: ',role);
        return (

            (
                role.name == 'Team'
                ?
                    <option value={ role.id }>{ role.name }</option>

                :
                    ''
            )
        );
    });

    const optionsCheckRoute = listRoute.map( (route, i) => {

        return (

            <div className="col-lg-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id={ 'idCheck'+ route.name } value={ route.name } onChange={ () => handleChange() }/>
                    <label class="form-check-label" for="gridCheck1">
                        { route.name }
                    </label>
                </div>
            </div>
        );
    });

    const handleChange = () => {

        let routesIds = '';

        listRoute.forEach( route => {

            if(document.getElementById('idCheck'+ route.name).checked)
            {
                routesIds = (routesIds == '' ? route.name : route.name +','+ routesIds);
            }
        });

        setIdsRoutes(routesIds);
    };

    const modalCategoryInsert = <React.Fragment>
                                    <div className="modal fade" id="modalCategoryInsert" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div className="modal-dialog">
                                            <form onSubmit={ handlerSaveTeam }>
                                                <div className="modal-content">
                                                    <div className="modal-header">
                                                        <h5 className="modal-title text-primary" id="exampleModalLabel">{ titleModal }</h5>
                                                        <button type="button" className="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div className="modal-body">
                                                        <div className="row">
                                                            <div className="col-lg-12">
                                                                <div className="form-group">
                                                                    <label>Role</label>
                                                                    <div id="idRole" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <select value={ idRole } className="form-control" onChange={ (e) => setIdRole(e.target.value) } required>
                                                                        { listRoleSelect }
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div className="row">
                                                            <div className="col-lg-6">
                                                                <div className="form-group">
                                                                    <label>Team Name</label>
                                                                    <div id="name" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="text" value={ name } className="form-control" onChange={ (e) => setName(e.target.value) } required/>
                                                                </div>
                                                            </div>
                                                            <div className="col-lg-6">
                                                                <div className="form-group">
                                                                    <label>Name of owner</label>
                                                                    <div id="nameOfOwner" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="text" value={ nameOfOwner } className="form-control" onChange={ (e) => setNameOfOwner(e.target.value) } required/>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div className="row">
                                                            <div className="col-lg-6">
                                                                <div className="form-group">
                                                                    <label>Phone</label>
                                                                    <div id="phone" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="text" value={ phone } className="form-control" onChange={ (e) => setPhone(e.target.value) } required/>
                                                                </div>
                                                            </div>
                                                            <div className="col-lg-6">
                                                                <div className="form-group">
                                                                    <label>Email</label>
                                                                    <div id="email" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="text" value={ email } className="form-control" onChange={ (e) => setEmail(e.target.value) } required/>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div className="row">
                                                            <div className="col-lg-6">
                                                                <div className="form-group">
                                                                    <label>Permission Dispatch</label>
                                                                    <select value={ permissionDispatch } className="form-control" onChange={ (e) => setPermissionDispatch(e.target.value) } required>
                                                                        <option value="0">No</option>
                                                                        <option value="1">Yes</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div className="col-lg-6">
                                                                <div className="form-group">
                                                                    <label>Id Onfleet</label>
                                                                    <input type="text" value={ idOnfleet } className="form-control" readOnly/>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div className="row" >
                                                            <div className="col-lg-12">
                                                                <div className="form-group">
                                                                    <label>Status</label>
                                                                    <div id="status" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <select value={ status } className="form-control" onChange={ (e) => setStatus(e.target.value) } required>
                                                                        <option value="Active" >Active</option>
                                                                        <option value="Inactive" >Inactive</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div className="row">
                                                            <div className="col-lg-12">
                                                                <div className="form-group">
                                                                    <label>Routes</label>
                                                                    <div id="idRole" className="text-danger" style={ {display: 'none'} }></div>
                                                                </div>
                                                                <div className="row form-group">
                                                                    { optionsCheckRoute }
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
            { modalCategoryInsert }
            <div className="row">
                <div className="col-lg-12">
                    <div className="card">
                        <div className="card-body">
                            <h5 className="card-title">
                                <div className="row form-group">
                                    <div className="col-lg-10">
                                        Team List
                                    </div>
                                    <div className="col-lg-2">
                                        <button className="btn btn-success btn-sm pull-right" title="Agregar" onClick={ () => handlerOpenModal(0) }>
                                            <i className="bx bxs-plus-square"></i>
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
                                                <th>NAME</th>
                                                <th>PHONE</th>
                                                <th>EMAIL</th>
                                                <th>ID ONFLEET</th>
                                                <th>STATUS</th>
                                                <th>ACTIONS</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            { listUserTable }
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
                        totalItemsCount={totalTeam}
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

export default Team;

// DOM element
if (document.getElementById('team')) {
    ReactDOM.render(<Team />, document.getElementById('team'));
}
