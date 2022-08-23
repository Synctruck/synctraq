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

        fetch(url_general +'viewer/list?page='+ pageNumber +'&textSearch='+ textSearch)
        .then(res => res.json())
        .then((response) => {
            setListUser(response.viewerList.data);
            setPage(response.viewerList.current_page);
            setTotalPage(response.viewerList.per_page);
            setTotalUser(response.viewerList.total);
        });
    }

    const listAllRole = (pageNumber) => {
        fetch(url_general +'role/list')
        .then(res => res.json())
        .then((response) => {
            setIdRole(5);
            setListRole(response.roleList);
        });
    }

    const handlerOpenModal = (id) => {

        clearValidation();

        if(id)
        {
            setTitleModal('Update Viewer')
            setTextButtonSave('Update');
        }
        else
        {
            listAllRole();
            clearForm();
            setTitleModal('Add Viewer');
            setTextButtonSave('Save');
        }

        let myModal = new bootstrap.Modal(document.getElementById('modalViewerInsert'), {

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

        clearValidation();

        if(id == 0)
        {
            let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            LoadingShow();

            fetch(url_general +'viewer/insert', {
                headers: { "X-CSRF-TOKEN": token },
                method: 'post',
                body: formData
            })
            .then(res => res.json()).
            then((response) => {
                    if(response.stateAction)
                    {
                        swal("Viewer was registered!", {

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

            fetch(url_general +'viewer/update/'+ id, {
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

        fetch(url_general +'viewer/get/'+ id)
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
            text: "Viewer will be deleted!",
            icon: "warning",
            buttons: true,
            dangerMode: true,
        })
        .then((willDelete) => {

            if(willDelete)
            {
                fetch(url_general +'viewer/delete/'+ id)
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
    }

    const listUserTable = listUser.map( (user, i) => {

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

                    <button className="btn btn-danger btn-sm" title="Eliminar" onClick={ () => deleteUser(user.id) }>
                        <i className="bx bxs-trash-alt"></i>
                    </button>
                </td>
            </tr>
        );
    });

    const listRoleSelect = listRole.map( (role, i) => {

        return (

            (
                role.name == 'View'
                ?
                    <option value={ role.id }>{ role.name }</option>

                :
                 ''
            )

        );
    });

    const modalViewerInsert = <React.Fragment>
                                    <div className="modal fade" id="modalViewerInsert" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
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
            { modalViewerInsert }
            <div className="row">
                <div className="col-lg-12">
                    <div className="card">
                        <div className="card-body">
                            <h5 className="card-title">
                                <div className="row form-group">
                                    <div className="col-lg-10">
                                        Viewers List
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
if (document.getElementById('viewer')) {
    ReactDOM.render(<User />, document.getElementById('viewer'));
}
