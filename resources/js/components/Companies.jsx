import React, { useState, useEffect } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'

function Companies() {

    const [id, setId]      = useState(0);
    const [name, setName]  = useState('');

    const [onHold, setOnHold]               = useState('');
    const [inbound, setInbound]             = useState('');
    const [dispatch, setDispatch]           = useState('');
    const [delivery, setDelivery]           = useState('');
    const [reInbound, setReInbound]         = useState('');
    const [returnCompany, setReturnCompany] = useState('');

    const [listCompany, setListCompany] = useState([]);

    const [page, setPage] = useState(1);
    const [totalPage, setTotalPage] = useState(0);
    const [totalComment, setTotalComment] = useState(0);

    const [titleModal, setTitleModal] = useState('');

    const [textSearch, setSearch] = useState('');
    const [textButtonSave, setTextButtonSave] = useState('Guardar');

    useEffect(() => {

        listAllCompany(page);

    }, [textSearch])

    const handlerChangePage = (pageNumber) => {

        listAllCompany(pageNumber);
    }

    const listAllCompany = (pageNumber) => {

        fetch(url_general +'company/list?page='+ pageNumber +'&textSearch='+ textSearch)
        .then(res => res.json())
        .then((response) => {

            setListCompany(response.companyList.data);
            setPage(response.companyList.current_page);
            setTotalPage(response.companyList.per_page);
            setTotalComment(response.companyList.total);
        });
    }

    const handlerOpenModal = (id) => {

        clearValidation();

        if(id)
        {
            setTitleModal('Update Comment')
            setTextButtonSave('Update');
        }
        else
        {
            clearForm();
            setTitleModal('Add Company')
            setTextButtonSave('Save');
        }

        let myModal = new bootstrap.Modal(document.getElementById('modalCategoryInsert'), {

            keyboard: false,
            backdrop: 'static',
        });

        myModal.show();
    }

    const handlerSaveRoute = (e) => {

        e.preventDefault();

        clearForm();

        const formData = new FormData();

        formData.append('name', name);
        formData.append('onHold', onHold);
        formData.append('inbound', inbound);
        formData.append('delivery', delivery);
        formData.append('dispatch', dispatch);
        formData.append('reInbound', reInbound);
        formData.append('returnCompany', returnCompany);

        clearValidation();

        if(id == 0)
        {
            let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            LoadingShow();

            fetch(url_general +'company/insert', {
                headers: { "X-CSRF-TOKEN": token },
                method: 'post',
                body: formData
            })
            .then(res => res.json()).
            then((response) => {

                    if(response.stateAction)
                    {
                        swal("Company was save!", {

                            icon: "success",
                        });

                        clearForm();
                        listAllCompany(1);
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

            fetch(url_general +'company/update/'+ id, {
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
                    listAllCompany(page);

                    swal("Comment updated!", {

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

    const getCompany = (id) => {

        fetch(url_general +'company/get/'+ id)
        .then(response => response.json())
        .then(response => {

            let company = response.company;
            let companyStatus = response.company.company_status;

            companyStatus.forEach( status => {

                if(status.status == 'ReInbound')
                {
                    setReInbound(status.statusCodeCompany);
                }
                else if(status.status == 'On hold')
                {
                    setOnHold(status.statusCodeCompany);
                }
                else if(status.status == 'Dispatch')
                {
                    setDispatch(status.statusCodeCompany);
                }
                else if(status.status == 'Delivery')
                {
                    setDelivery(status.statusCodeCompany);
                }
                else if(status.status == 'Inbound')
                {
                    setInbound(status.statusCodeCompany);
                }
                else if(status.status == 'ReturnCompany')
                {
                    setReturnCompany(status.statusCodeCompany);
                }
            });

            setId(company.id);
            setName(company.name);

            handlerOpenModal(company.id);
        });
    }

    const deleteCompany = (id) => {

        swal({
            title: "You want to delete?",
            text: "Company will be removed!",
            icon: "warning",
            buttons: true,
            dangerMode: true,
        })
        .then((willDelete) => {

            if(willDelete)
            {
                fetch(url_general +'company/delete/'+ id)
                .then(response => response.json())
                .then(response => {

                    if(response.stateAction)
                    {
                        swal("Company deleted successfully!", {

                            icon: "success",
                        });

                        listAllCompany(page);
                    }
                });
            } 
        });
    }

    const clearForm = () => {

        setId(0);
        setName('');
        setOnHold('');
        setInbound('');
        setDispatch('');
        setDelivery('');
        setReInbound('');
        setReturnCompany('');
    }

    const clearValidation = () => {

        document.getElementById('name').style.display = 'none';
        document.getElementById('name').innerHTML     = '';
    }

    const listCompanyTable = listCompany.map( (company, i) => {

        let buttonDelete =  <button className="btn btn-danger btn-sm" title="Eliminar" onClick={ () => deleteCompany(company.id) }>
                                <i className="bx bxs-trash-alt"></i>
                            </button>

        /*if(company.histories.length == 0)
        {
            buttonDelete =  <button className="btn btn-danger btn-sm" title="Eliminar" onClick={ () => deleteCompany(company.id) }>
                                <i className="bx bxs-trash-alt"></i>
                            </button>
        }*/

        let status1 = <p><b>{ company.company_status[0].status }</b>: { company.company_status[0].statusCodeCompany }</p>;
        let status2 = <p><b>{ company.company_status[1].status }</b>: { company.company_status[1].statusCodeCompany }</p>;
        let status3 = <p><b>{ company.company_status[2].status }</b>: { company.company_status[2].statusCodeCompany }</p>;
        let status4 = <p><b>{ company.company_status[3].status }</b>: { company.company_status[3].statusCodeCompany }</p>;
        let status5 = <p><b>{ company.company_status[4].status }</b>: { company.company_status[4].statusCodeCompany }</p>;
        let status6 = <p><b>{ company.company_status[5].status }</b>: { company.company_status[5].statusCodeCompany }</p>;

        return (

            <tr key={i}>
                <td>{ company.name }</td>
                <td>{ company.key_api }</td>
                <td>
                    { status1 }
                    { status2 }
                    { status3 }
                    { status4 }
                    { status5 }
                    { status6 }
                </td>
                <td>
                    <button className="btn btn-primary btn-sm" title="Editar" onClick={ () => getCompany(company.id) }>
                        <i className="bx bx-edit-alt"></i>
                    </button> &nbsp;
                    { buttonDelete }
                </td>
            </tr>
        );
    });

    const modalCategoryInsert = <React.Fragment>
                                    <div className="modal fade" id="modalCategoryInsert" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div className="modal-dialog">
                                            <form onSubmit={ handlerSaveRoute }>
                                                <div className="modal-content">
                                                    <div className="modal-header">
                                                        <h5 className="modal-title text-primary" id="exampleModalLabel">{ titleModal }</h5>
                                                        <button type="button" className="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div className="modal-body">
                                                        <div className="row">
                                                            <div className="col-lg-12 form-group">
                                                                <label>Company Name</label>
                                                                <div id="name" className="text-danger" style={ {display: 'none'} }></div>
                                                                <input type="text" className="form-control" value={ name } maxLength="100" onChange={ (e) => setName(e.target.value) } required/>
                                                            </div>
                                                        </div>
                                                        <div className="row">
                                                            <div className="col-lg-6 form-group">
                                                                <label>On Hold</label>
                                                                <input type="text" className="form-control" value={ onHold } maxLength="100" onChange={ (e) => setOnHold(e.target.value) }/>
                                                            </div>
                                                            <div className="col-lg-6 form-group">
                                                                <label>Inbound</label>
                                                                <input type="text" className="form-control" value={ inbound } maxLength="100" onChange={ (e) => setInbound(e.target.value) }/>
                                                            </div>
                                                            <div className="col-lg-6 form-group">
                                                                <label>Dispatch</label>
                                                                <input type="text" className="form-control" value={ dispatch } maxLength="100" onChange={ (e) => setDispatch(e.target.value) }/>
                                                            </div>
                                                            <div className="col-lg-6 form-group">
                                                                <label>Delivery</label>
                                                                <input type="text" className="form-control" value={ delivery } maxLength="100" onChange={ (e) => setDelivery(e.target.value) }/>
                                                            </div>
                                                            <div className="col-lg-6 form-group">
                                                                <label>Re-Inbound</label>
                                                                <input type="text" className="form-control" value={ reInbound } maxLength="100" onChange={ (e) => setReInbound(e.target.value) }/>
                                                            </div>
                                                            <div className="col-lg-6 form-group">
                                                                <label>Retur-Company</label>
                                                                <input type="text" className="form-control" value={ returnCompany } maxLength="100" onChange={ (e) => setReturnCompany(e.target.value) }/>
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
                                    <div className="col-lg-10"> 
                                        Companies List
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
                                    <input type="text" value={textSearch} onChange={ (e) => setSearch(e.target.value) } className="form-control" placeholder="Search..."/>
                                    <br/>
                                </div>
                            </div>
                            <div className="row form-group">
                                <div className="col-lg-12">
                                    <table className="table table-hover table-condensed">
                                        <thead>
                                            <tr>
                                                <th>COMPANY NAME</th>
                                                <th>KEY API</th>
                                                <th>STATUS</th>
                                                <th>ACTIONS</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            { listCompanyTable }
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
                        totalItemsCount={totalComment}
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

export default Companies;

// DOM element
if (document.getElementById('companies')) {
    ReactDOM.render(<Companies />, document.getElementById('companies'));
}