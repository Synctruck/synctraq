import React, { useState, useEffect } from 'react'
import ReactDOM from 'react-dom'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'
import axios from 'axios';

function Roles() {

    const [id, setId]            = useState(0);
    const [name, setName]        = useState('');
    const [status, setStatus]        = useState(1);

    const [rolesList, setRolesList] = useState([]);
    const [permissionsList, setPermissionsList] = useState([]);
    const [rolePermissions, setRolePermissions] = useState([]);

    const [page, setPage] = useState(1);
    const [totalPage, setTotalPage] = useState(0);
    const [totalRoles, setTotalRoles] = useState(0);

    const [titleModal, setTitleModal] = useState('');
    const [checkAll, setCheckAll] = useState(false);

    const [textSearch, setSearch] = useState('');
    const [textButtonSave, setTextButtonSave] = useState('Guardar');

    useEffect(() => {
        listAllPermissions();
    }, [])

    useEffect(() => {

        listAllRoles(page);

    }, [textSearch])


    const handlerChangePage = (pageNumber) => {

        listAllRoles(pageNumber);
    }

    const listAllRoles = (pageNumber) => {

        fetch(url_general +'roles/getList?page='+ pageNumber +'&textSearch='+ textSearch)
        .then(res => res.json())
        .then((response) => {

            setRolesList(response.roles.data);
            setPage(response.roles.current_page);
            setTotalPage(response.roles.per_page);
            setTotalRoles(response.roles.total);

        });
    }

    const listAllPermissions = () => {

        fetch(url_general +'roles/getPermissions')
        .then(res => res.json())
        .then((response) => {
            setPermissionsList(response.permissions);
        });
    }

    const handleCheckedAll = ()=>{
        setCheckAll(!checkAll);
        console.log('check all');
        if(checkAll== false ){

            let permissions = []
            permissionsList.map(function(element) {
                permissions.push(element.id)
            });

            setRolePermissions(permissions);
        }
        else{

            setRolePermissions([]);
        }
    }

    const handleCheckPermissions = (event) => {
        var updatedList = [...rolePermissions];
        if (event.target.checked) {
          updatedList = [...rolePermissions, +event.target.value];
        } else {
            updatedList = updatedList.filter(function(item) {
                return item != event.target.value
            })
        }
        setRolePermissions(updatedList);
    };

    const handlerOpenModal = (id) => {

        clearValidation();

        if(id)
        {
            setTitleModal('Update Role')
            setTextButtonSave('Update');
        }
        else
        {
            clearForm();
            setTitleModal('Add Role')
            setTextButtonSave('Save');
        }

        let myModal = new bootstrap.Modal(document.getElementById('modalCategoryInsert'), {

            keyboard: true
        });

        myModal.show();
    }


    const handlerSaveRole = (e) => {

        e.preventDefault();

        clearValidation();
        LoadingShow();
        let url = url_general +'roles/insert'
        let method = 'POST'

        if(id != 0){
            url = url_general +'roles/update/'+ id
            method = 'PUT'
        }

        axios({
            method: method,
            url: url,
            data: {name,permissions:rolePermissions,status:status}
        })
        .then((response) => {
            swal("Role was recorded!", {
                icon: "success",
            });

            listAllRoles(1);
            if(id==0)
                clearForm();
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

    const editRole = (id) => {

        fetch(url_general +'roles/'+ id)
        .then(response => response.json())
        .then(response => {

            let role = response.role;
            setId(role.id);
            setName(role.name);
            setStatus(role.status);
            handlerOpenModal(role.id);

            let permissions = []
            role.permissions.map(function(element) {
                permissions.push(element.id)
            });

            setRolePermissions(permissions);
        });
    }

    const clearForm = () => {

        setId(0);
        setName('');
        setRolePermissions([]);
    }

    const clearValidation = () => {

        document.getElementById('name').style.display = 'none';
        document.getElementById('name').innerHTML     = '';
    }

    const renderListPermission = permissionsList.map((item,index)=>{


        if(item.slug == 'T'){
            return <span><b>{item.name}</b> </span>;
        }
        if(item.slug!='T' && item.parent_id !=null){
            return <div class="checkbox checkbox-primary mb-1" style={{paddingLeft:'30px'}} >
                        <input
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

    const rolesListTable = rolesList.map( (role, i) => {

        return (

            <tr key={i}>
                <td>{ i+1 }</td>
                <td>{ role.name }</td>
                <td>
                    <button className="btn btn-primary btn-sm" title="Editar" onClick={ () => editRole(role.id) }>
                        <i className="bx bx-edit-alt"></i>
                    </button> &nbsp;
                </td>
            </tr>
        );
    });

    const modalCategoryInsert = <React.Fragment>
                                    <div className="modal fade" id="modalCategoryInsert" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div className="modal-dialog modal-dialog-scrollable">
                                            <form onSubmit={ handlerSaveRole }>
                                                <div className="modal-content">
                                                    <div className="modal-header">
                                                        <h5 className="modal-title text-primary" id="exampleModalLabel">{ titleModal }</h5>
                                                        <button type="button" className="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div className="modal-body">
                                                        <div className="row">
                                                            <div className="col-lg-12 form-group">
                                                                <label>Role name</label>
                                                                <div id="name" className="text-danger" style={ {display: 'none'} }></div>
                                                                <input type="text" className="form-control" value={ name } maxLength="100" onChange={ (e) => setName(e.target.value) } required/>
                                                            </div>
                                                        </div>
                                                        <div className="row">
                                                            <div className="col-lg-12 form-group">
                                                                <label> Login Status</label>
                                                                <div id="name" className="text-danger" style={ {display: 'none'} }></div>
                                                                <select className='form-control' value={status} onChange={(e) => setStatus(e.target.value)}>
                                                                    <option value="1">Enabled</option>
                                                                    <option value="0">Disabled</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <span>Permissions</span><hr />
                                                            <div class="col-md-12" >
                                                                <div class="checkbox checkbox-primary mb-1">
                                                                    <label htmlFor="chkAll">
                                                                        <input id='chkAll' type="checkbox" defaultChecked={checkAll} onChange={() => handleCheckedAll()} /> Select all
                                                                    </label>
                                                                </div>

                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        {renderListPermission}                                                                   </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div className="modal-footer">
                                                        <button type="button" className="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        {1 == 1 && <button className="btn btn-primary">{ textButtonSave }</button> }

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
                                    <div className="col-lg-9">
                                        Roles List
                                    </div>
                                    <div className="col-lg-1">
                                        {/* <button className="btn btn-success btn-sm pull-right form-control" title="Agregar" onClick={ () => handlerOpenModal(0) }>
                                            <i className="bx bxs-plus-square"></i> Add
                                        </button> */}
                                    </div>
                                </div>
                            </h5>
                            <div className="row form-group">
                                <div className="col-lg-12">
                                    <input type="text" value={textSearch} onChange={ (e) => setSearch(e.target.value) } className="form-control" placeholder="Search..."/>
                                    <br/>
                                </div>
                            </div>
                            <div className="row form-group">
                                <div className="col-lg-12">
                                    <table className="table table-hover table-condensed">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>ROLE NAME</th>
                                                <th>ACTIONS</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            { rolesListTable }
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
                        totalItemsCount={totalRoles}
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

export default Roles;

// DOM element
if (document.getElementById('roles')) {
    ReactDOM.render(<Roles />, document.getElementById('roles'));
}
