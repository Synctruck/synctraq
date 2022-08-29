import React, { useState, useEffect } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'
import Log from 'laravel-mix/src/Log'

function User() {

    const [id, setId]                   = useState(0);
    const [idRole, setIdRole]           = useState(0);
    const [name, setName]               = useState('');
    const [nameOfOwner, setNameOfOwner] = useState('');
    const [address, setAddress]         = useState('');
    const [phone, setPhone]             = useState('');
    const [email, setEmail]             = useState('');
    const [password, setPassword]       = useState('');

    const [viewInputPassword, setViewInputPassword] = useState(true);

    const [listUser, setListUser] = useState([]);
    const [listRole, setListRole] = useState([]);

    const [page, setPage] = useState(1);
    const [totalPage, setTotalPage] = useState(0);
    const [totalUser, setTotalUser] = useState(0);

    const [titleModal, setTitleModal] = useState('');

    const [textSearch, setSearch] = useState('');
    const [textButtonSave, setTextButtonSave] = useState('Save');

    useEffect(() => {

        listAllUser(page);

    }, [textSearch])

    const handlerChangePage = (pageNumber) => {

        listAllUser(pageNumber);
    }

    const listAllUser = (pageNumber) => {

        fetch(url_general +'validator/list?page='+ pageNumber +'&textSearch='+ textSearch)
        .then(res => res.json())
        .then((response) => {
            setListUser(response.validatorList.data);
            setPage(response.validatorList.current_page);
            setTotalPage(response.validatorList.per_page);
            setTotalUser(response.validatorList.total);
        });
    }

    const listAllRole = (pageNumber) => {
        fetch(url_general +'role/list')
        .then(res => res.json())
        .then((response) => {
            setIdRole(2);
            setListRole(response.roleList);
        });
    }

    const handlerOpenModal = (id) => {

        clearValidation();

        if(id)
        {
            setTitleModal('Update validator')
            setTextButtonSave('Update');
        }
        else
        {
            listAllRole();
            clearForm();

            setTitleModal('Add validator');
            setTextButtonSave('Save');
            setViewInputPassword(true);
        }

        let myModal = new bootstrap.Modal(document.getElementById('modalvalidatorInsert'), {

            keyboard: true
        });

        myModal.show();
    }

    const handlerSaveUser = (e) => {

        e.preventDefault();

        const formData = new FormData();

        formData.append('idRole', idRole);
        formData.append('name', name);
        formData.append('nameOfOwner', nameOfOwner);
        formData.append('address', address);
        formData.append('phone', phone);
        formData.append('email', email);
        formData.append('password', password);

        clearValidation();

        if(id == 0)
        {
            let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            LoadingShow();

            fetch(url_general +'validator/insert', {
                headers: { "X-CSRF-TOKEN": token },
                method: 'post',
                body: formData
            })
            .then(res => res.json()).
            then((response) => {
                    if(response.stateAction)
                    {
                        swal("validator was registered!", {

                            icon: "success",
                        });

                        listAllUser(1);
                        clearForm();
                    }
                    else(response.status == 422)
                    {
                        for(const index in response.errors)
                        {
                            document.getElementById(index).style.display = 'block';
                            document.getElementById(index).innerHTML     = response.errors[index][0];
                        }
                    }

                    LoadingHide();
                },
            );
        }
        else
        {
            LoadingShow();

            let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            fetch(url_general +'validator/update/'+ id, {
                headers: {
                    "X-CSRF-TOKEN": token
                },
                method: 'post',
                body: formData
            })
            .then(res => res.json()).
            then((response) => {

                if(response.stateAction)
                {
                    listAllUser(1);

                    swal("User was updated!", {

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

                LoadingHide();
            });
        }
    }

    const getUser = (id) => {

        listAllRole();

        setViewInputPassword(false);
        
        fetch(url_general +'validator/get/'+ id)
        .then(response => response.json())
        .then(response => {

            let user = response.user;

            setId(user.id);
            setIdRole(user.idRole);
            setName(user.name);
            setNameOfOwner(user.nameOfOwner);
            setAddress(user.address);
            setPhone(user.phone);
            setEmail(user.email);

            handlerOpenModal(user.id);
        });
    }

    const deleteUser = (id) => {

        swal({
            title: "You want to delete?",
            text: "validator will be deleted!",
            icon: "warning",
            buttons: true,
            dangerMode: true,
        })
        .then((willDelete) => {

            if(willDelete)
            {
                fetch(url_general +'validator/delete/'+ id)
                .then(response => response.json())
                .then(response => {

                    if(response.stateAction)
                    {
                        swal("User successfully deleted!", {

                            icon: "success",
                        });

                        listAllUser(page);
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
        setPassword('');
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

        document.getElementById('password').style.display = 'none';
        document.getElementById('password').innerHTML     = '';
    }

    const listUserTable = listUser.map( (user, i) => {
        let buttonDelete ='';
        if (!user.history && user.routes_team.length == 0 && user.package_not_exists.length == 0 ) {
            buttonDelete = <button className="btn btn-danger btn-sm" title="Eliminar" onClick={ () => deleteUser(user.id) }>
                                <i className="bx bxs-trash-alt"></i>
                            </button>;
        }

        return (

            <tr key={i}>
                <td>{ user.name }</td>
                <td>{ user.nameOfOwner }</td>
                <td>{ user.address }</td>
                <td>{ user.phone }</td>
                <td>{ user.email }</td>
                <td>
                    <button className="btn btn-primary btn-sm" title="Editar" onClick={ () => getUser(user.id) }>
                        <i className="bx bx-edit-alt"></i>
                    </button> &nbsp;

                   { buttonDelete }

                </td>
            </tr>
        );
    });

    const listRoleSelect = listRole.map( (role, i) => {

        return (

            (
                role.name == 'Validador'
                ?
                    <option value={ role.id }>{ role.name }</option>

                :
                 ''
            )

        );
    });

    const modalvalidatorInsert = <React.Fragment>
                                    <div className="modal fade" id="modalvalidatorInsert" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div className="modal-dialog">
                                            <form onSubmit={ handlerSaveUser }>
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
                                                                    <label>First Name</label>
                                                                    <div id="name" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="text" value={ name } className="form-control" onChange={ (e) => setName(e.target.value) } required/>
                                                                </div>
                                                            </div>
                                                            <div className="col-lg-6">
                                                                <div className="form-group">
                                                                    <label>Last Name</label>
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
                                                                    <input type="email" value={ email } className="form-control" onChange={ (e) => setEmail(e.target.value) } required/>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div className="row" style={ { display: (viewInputPassword ? 'block' : 'none' )}  }>
                                                            <div className="col-lg-12">
                                                                <div className="form-group">
                                                                    <label>Password</label>
                                                                    <div id="password" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="password" value={ password } className="form-control" onChange={ (e) => setPassword(e.target.value) } required={ viewInputPassword ? true : false }/>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div className="modal-footer">
                                                        <button type="button" className="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        <button className="btn btn-primary">{ textButtonSave }</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </React.Fragment>;

    return (

        <section className="section">
            { modalvalidatorInsert }
            <div className="row">
                <div className="col-lg-12">
                    <div className="card">
                        <div className="card-body">
                            <h5 className="card-title">
                                <div className="row form-group">
                                    <div className="col-lg-10">
                                        Validators List
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
                                                <th>FIRST NAME</th>
                                                <th>LAST NAME</th>
                                                <th>ADDREESS</th>
                                                <th>PHONE</th>
                                                <th>EMAIL</th>
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

export default User;

// DOM element
if (document.getElementById('validator')) {
    ReactDOM.render(<User />, document.getElementById('validator'));
}
