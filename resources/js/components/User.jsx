import React, { useState, useEffect } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'

function User() {

    const [id, setId]                   = useState(0);
    const [idRole, setIdRole]           = useState(0);
    const [idCellar, setIdCellar] = useState('');
    const [idRoleFilter, setIdRoleFilter]  = useState('');
    const [name, setName]               = useState('');
    const [nameOfOwner, setNameOfOwner] = useState('');
    const [address, setAddress]         = useState('');
    const [phone, setPhone]             = useState('');
    const [email, setEmail]             = useState('');
    const [password, setPassword]       = useState('');
    const [status, setStatus]           = useState('');
    const [statusFilter, setStatusFilter] = useState('Active');

    const [viewInputPassword, setViewInputPassword] = useState(true);

    const [listUser, setListUser] = useState([]);
    const [listRole, setListRole] = useState([]);
    const [listCellar, setListCellar] = useState([]);

    const [page, setPage] = useState(1);
    const [totalPage, setTotalPage] = useState(0);
    const [totalUser, setTotalUser] = useState(0);

    const [titleModal, setTitleModal] = useState('');

    const [textSearch, setSearch] = useState('');
    const [textButtonSave, setTextButtonSave] = useState('Save');

    useEffect(()=> {
        listAllRole();
        listAllCellar();
    },[]);
    useEffect(() => {

        listAllUser(page);

    }, [textSearch,idRoleFilter,statusFilter])

    const handlerChangePage = (pageNumber) => {

        listAllUser(pageNumber);
    }

    const listAllUser = (pageNumber) => {

        fetch(url_general +'user/list?page='+ pageNumber +'&textSearch='+ textSearch+'&idRole='+ idRoleFilter+'&status='+ statusFilter)
        .then(res => res.json())
        .then((response) => {

            setListUser(response.userList.data);
            setPage(response.userList.current_page);
            setTotalPage(response.userList.per_page);
            setTotalUser(response.userList.total);
        });
    }

    const listAllRole = (pageNumber) => {

        fetch(url_general +'role/list')
        .then(res => res.json())
        .then((response) => {

            setListRole(response.roleList);
        });
    }

    const listAllCellar = () => {

        fetch(url_general +'cellar/get-all')
        .then(res => res.json())
        .then((response) => {

            setListCellar(response.cellarList);
        });
    }

    const handlerOpenModal = (id) => {

        clearValidation();

        if(id)
        {
            setTitleModal('Update User')
            setTextButtonSave('Update');
        }
        else
        {
            // listAllRole();
            clearForm();
            setTitleModal('Add User');
            setTextButtonSave('Save');
            setViewInputPassword(true);
        }

        let myModal = new bootstrap.Modal(document.getElementById('modalCategoryInsert'), {

            keyboard: true
        });

        myModal.show();
    }

    const handlerSaveUser = (e) => {

        e.preventDefault();

        const formData = new FormData();

        formData.append('idRole', idRole);
        formData.append('idCellar', idCellar);
        formData.append('name', name);
        formData.append('nameOfOwner', nameOfOwner);
        formData.append('address', address);
        formData.append('phone', phone);
        formData.append('email', email);
        formData.append('password', password);
        formData.append('status', status);

        clearValidation();

        if(id == 0)
        {
            let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            LoadingShow();

            fetch(url_general +'user/insert', {
                headers: { "X-CSRF-TOKEN": token },
                method: 'post',
                body: formData
            })
            .then(res => res.json()).
            then((response) => {

                    if(response.stateAction)
                    {
                        swal("User was registered!", {

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

            fetch(url_general +'user/update/'+ id, {
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

        // listAllRole();

        setViewInputPassword(false);

        fetch(url_general +'user/get/'+ id)
        .then(response => response.json())
        .then(response => {

            let user = response.user;

            setId(user.id);
            setIdRole(user.idRole);
            setIdCellar((user.idCellar != 0 ? user.idCellar : ''));
            setName(user.name);
            setNameOfOwner(user.nameOfOwner);
            setAddress(user.address);
            setPhone(user.phone);
            setEmail(user.email);
            setStatus(user.status);

            handlerOpenModal(user.id);
        });
    }

    const deleteUser = (id) => {

        swal({
            title: "You want to delete?",
            text: "User will be deleted!",
            icon: "warning",
            buttons: true,
            dangerMode: true,
        })
        .then((willDelete) => {

            if(willDelete)
            {
                fetch(url_general +'user/delete/'+ id)
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
        setIdCellar('');
        setName('');
        setNameOfOwner('');
        setAddress('');
        setPhone('');
        setEmail('');
        setStatus('Active');
        setPassword('');
    }

    const clearValidation = () => {

        document.getElementById('idRole').style.display = 'none';
        document.getElementById('idRole').innerHTML     = '';

        document.getElementById('idCellar').style.display = 'none';
        document.getElementById('idCellar').innerHTML     = '';

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

        document.getElementById('password').style.display = 'none';
        document.getElementById('password').innerHTML     = '';
    }


    const listUserTable = listUser.map( (user, i) => {

        return (

            <tr key={i}>
                <td>{ user.role.name }</td>
                <td>{ user.name }</td>
                <td>{ user.nameOfOwner }</td>
                <td>{ user.address }</td>
                <td>{ user.phone }</td>
                <td>{ user.email }</td>
                <td>
                    {
                        (
                            user.cellar
                            ?
                                user.cellar.name
                            :
                                ''
                        )
                    }
                </td>
                <td>
                    {
                        (
                            user.status == 'Active'
                            ?
                                <div className="alert alert-success font-weight-bold">{ user.status }</div>
                            :
                                <div className="alert alert-danger font-weight-bold">{ user.status }</div>
                        )
                    }
                </td>
                <td>
                    <button className="btn btn-primary btn-sm" title="Editar" onClick={ () => getUser(user.id) }>
                        <i className="bx bx-edit-alt"></i>
                    </button> &nbsp;

                    {
                        (
                            user.deleteUser == 0
                            ?
                                <button className="btn btn-danger btn-sm" title="Eliminar" onClick={ () => deleteUser(user.id) }>
                                    <i className="bx bxs-trash-alt"></i>
                                </button>
                            :
                                ''
                        )
                    }
                </td>
            </tr>
        );
    });

    const listRoleFilter = listRole.map( (role, i) => {
        console.log('role: ',role.name);
        return (

            (
                (role.id != 3 && role.id != 4 )
                ?
                    <option value={ role.id }>{ role.name }</option>

                :
                 ''
            )

        );
    });

    const listRoleSelect = listRole.map( (role, i) => {
        console.log('dsf')
        return (

            (
                (role.id != 3 && role.id != 4 )
                ?
                    <option value={ role.id }>{ role.name }</option>

                :
                 ''
            )

        );
    });

    const listCellarTable = listCellar.map( (cellar, i) => {

        return (
            <option value={ cellar.id }>{ cellar.name }</option>
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

    const modalCategoryInsert = <React.Fragment>
                                    <div className="modal fade" id="modalCategoryInsert" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
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
                                                                        <option value="" style={ {display: 'none'} }>Seleccione un rol</option>
                                                                        { listRoleSelect }
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div className="row">
                                                            <div className="col-lg-12">
                                                                <div className="form-group">
                                                                    <label className="form">Warehouse</label>
                                                                    <div id="idCellar" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <select value={ idCellar } className="form-control" onChange={ (e) => setIdCellar(e.target.value) }>
                                                                        <option value="">Select a cellar</option>
                                                                        { listCellarTable }
                                                                    </select>
                                                                </div>
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
                                                                    <input type="text" value={ phone } className="form-control" onChange={ (e) => setPhone(e.target.value) } required/>
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

                                                        <div className="row" style={ { display: (viewInputPassword ? 'block' : 'none' )}  }>
                                                            <div className="col-lg-12">
                                                                <div className="form-group">
                                                                    <label className="form">Password</label>
                                                                    <div id="password" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="password" value={ password } className="form-control" onChange={ (e) => setPassword(e.target.value) } required={ viewInputPassword ? true : false }/>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div className="row" >
                                                            <div className="col-lg-12">
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
                                                        <button className="btn btn-primary">{ textButtonSave }</button>
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
                                    <div className="col-lg-2">
                                        <button className="btn btn-success pull-right form-control" title="Agregar" onClick={ () => handlerOpenModal(0) }>
                                            <i className="bx bxs-plus-square"></i> Add
                                        </button>
                                    </div>
                                </div>
                            </h5>
                            <div className="row form-group mb-5">
                                <div className="col-lg-4">
                                    <label htmlFor="" className="form">Name</label>
                                    <input type="text" value={textSearch} onChange={ (e) => setSearch(e.target.value) } className="form-control" placeholder="Search..."/>
                                </div>
                                <div className="col-lg-4">
                                    <label htmlFor="" className="form">Role</label>
                                    <select value={ idRoleFilter } className="form-control" onChange={ (e) => setIdRoleFilter(e.target.value) } required>
                                        <option value="">All</option>
                                        { listRoleFilter }
                                    </select>
                                </div>
                                <div className="col-lg-4">
                                    <label htmlFor="" className="form">Status</label>
                                    <select value={ statusFilter } className="form-control" onChange={ (e) => setStatusFilter(e.target.value) } required>
                                        <option value="Active">Active</option>
                                        <option value="Inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                            <div className="row form-group">
                                <div className="col-lg-12">
                                    <table className="table table-hover table-condensed">
                                        <thead>
                                            <tr>
                                                <th>ROLE</th>
                                                <th>FIRST NAME</th>
                                                <th>LAST NAME</th>
                                                <th>ADDREESS</th>
                                                <th>PHONE</th>
                                                <th>EMAIL</th>
                                                <th>WAREHOUSE</th>
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
if (document.getElementById('user')) {
    ReactDOM.render(<User />, document.getElementById('user'));
}
