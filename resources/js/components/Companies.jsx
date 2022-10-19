import React, { useState, useEffect } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'

function Companies() {

    const [id, setId]                     = useState(0);
    const [name, setName]                 = useState('');
    const [email, setEmail]               = useState('');
    const [password, setPassword]         = useState('');
    const [lengthField, setLengthField]   = useState('');
    const [typeServices, setTypeServices] = useState('');
    const [status, setStatus] = useState('');
    const [keyWebhook, setKeyWebhook]     = useState('');
    const [urlWebhook, setUrlWebhook]     = useState('');

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

    const handlerSaveCategory = (e) => {

        e.preventDefault();

        const formData = new FormData();

        formData.append('name', name);
        formData.append('email', email);
        formData.append('password', password);
        formData.append('length_field', lengthField);
        formData.append('typeServices', typeServices);
        formData.append('status', status);
        formData.append('key_webhook', keyWebhook);
        formData.append('url_webhook', urlWebhook);
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
                    else if(response.status == 422)
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
            setEmail(company.email);
            setPassword(company.email);
            setTypeServices(company.typeServices);
            setStatus(company.status);
            setLengthField(company.length_field);
            setKeyWebhook(company.key_webhook);
            setUrlWebhook(company.url_webhook);

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
        setEmail('');
        setPassword('');
        setTypeServices('');
        setLengthField('');
        setStatus('');
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

        document.getElementById('email').style.display = 'none';
        document.getElementById('email').innerHTML     = '';

        document.getElementById('password').style.display = 'none';
        document.getElementById('password').innerHTML     = '';

        document.getElementById('typeServices').style.display = 'none';
        document.getElementById('typeServices').innerHTML     = '';

        document.getElementById('length_field').style.display = 'none';
        document.getElementById('length_field').innerHTML     = '';

        document.getElementById('status').style.display = 'none';
        document.getElementById('status').innerHTML     = '';
    }

    const [idCompany, setIdCompany]                     = useState(0);
    const [baseRatesList, setBaseRatesList]             = useState([]);
    const [titleModalBaseRates, setTitleModalBaseRates] = useState('');

    const getBaseRates = (idCompany, nameCompany) => {

        setIdCompany(idCompany);
        setTitleModalBaseRates('Base Rates - '+ nameCompany);

        fetch(url_general +'base-rates/list/'+ idCompany)
        .then(response => response.json())
        .then(response => {

            setBaseRatesList(response.baseRatesList);
            handlerOpenModalBaseRates();
        });
    }

    const handlerOpenModalBaseRates = () => {

        let myModal = new bootstrap.Modal(document.getElementById('modalBaseRates'), {

            keyboard: false,
            backdrop: 'static',
        });

        myModal.show();
    }

    const listBaseRatesTable = baseRatesList.map( (baseRates, i) => {

        return (

            <tr key={ i }>
                <td><b>{ baseRates.weight}</b></td>
                <td>
                    <input type="text" id={ 'base'+ baseRates.id } value={ baseRates.base }/>
                </td>
            </tr>
        );
    });

    const listCompanyTable = listCompany.map( (company, i) => {

        let buttonDelete =  <button className="btn btn-danger btn-sm" title="Eliminar" onClick={ () => deleteCompany(company.id) }>
                                <i className="bx bxs-trash-alt"></i>
                            </button>

        let status1 = <p><b>{ company.company_status[0].status }</b>: { company.company_status[0].statusCodeCompany }</p>;
        let status2 = <p><b>{ company.company_status[1].status }</b>: { company.company_status[1].statusCodeCompany }</p>;
        let status3 = <p><b>{ company.company_status[2].status }</b>: { company.company_status[2].statusCodeCompany }</p>;
        let status4 = <p><b>{ company.company_status[3].status }</b>: { company.company_status[3].statusCodeCompany }</p>;
        let status5 = <p><b>{ company.company_status[4].status }</b>: { company.company_status[4].statusCodeCompany }</p>;
        let status6 = <p><b>{ company.company_status[5].status }</b>: { company.company_status[5].statusCodeCompany }</p>;

        return (

            <tr key={i}>
                <td><b>{ company.name }</b></td>
                <td>{ company.email }</td>
                <td>{ company.key_api }</td>
                <td>{ company.key_webhook }</td>
                <td title={ company.url_webhook }>
                    {
                        (company.url_webhook)
                        ?
                            'url_webhook'
                        :
                            ''
                    }
                </td>
                <td>{ company.typeServices }</td>
                <td>
                    { status1 }
                    { status2 }
                    { status3 }
                    { status4 }
                    { status5 }
                    { status6 }
                </td>
                <td>
                    {
                        (company.status == 'Active')
                        ?
                            <div className="alert alert-success"><b>{ company.status }</b></div>
                        :
                            <div className="alert alert-danger"><b>{ company.status }</b></div>
                    }
                </td>
                <td style={ {display: 'none'} }>
                    <button className="btn btn-warning btn-sm form-control" title="Editar" onClick={ () => getBaseRates(company.id, company.name) }>
                        Base Rates
                    </button> &nbsp;
                    <button className="btn btn-warning btn-sm form-control" title="Editar" onClick={ () => getCompany(company.id) }>
                        Range diesel
                    </button> &nbsp;&nbsp;
                    <button className="btn btn-warning btn-sm form-control" title="Editar" onClick={ () => getCompany(company.id) }>
                        Dim Factor
                    </button> &nbsp;
                    <button className="btn btn-warning btn-sm form-control" title="Editar" onClick={ () => getCompany(company.id) }>
                        Peake Season
                    </button> &nbsp;
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

    const modalBaseRates = <React.Fragment>
                                    <div className="modal fade" id="modalBaseRates" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div className="modal-dialog">
                                            <form onSubmit="">
                                                <div className="modal-content">
                                                    <div className="modal-header">
                                                        <h5 className="modal-title text-primary" id="exampleModalLabel">{ titleModalBaseRates }</h5>
                                                        <button type="button" className="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div className="modal-body">
                                                        <div className="row">
                                                            <div className="col-lg-12">
                                                                <table className="table table-hover table-condensed">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>WEIGHT</th>
                                                                            <th>BASE</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        { listBaseRatesTable }
                                                                    </tbody>
                                                                </table>
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

    const modalCategoryInsert = <React.Fragment>
                                    <div className="modal fade" id="modalCategoryInsert" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div className="modal-dialog">
                                            <form onSubmit={ handlerSaveCategory }>
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
                                                            <div className={ (id == 0 ? 'col-lg-6 form-group' : 'col-lg-12 form-group') }>
                                                                <label>Email</label>
                                                                <div id="email" className="text-danger" style={ {display: 'none'} }></div>
                                                                <input type="email" className="form-control" value={ email } maxLength="100" onChange={ (e) => setEmail(e.target.value) } required/>
                                                            </div>
                                                            <div className="col-lg-6 form-group" style={ {display: (id == 0 ? 'block' : 'none')} }>
                                                                <label>Password</label>
                                                                <div id="password" className="text-danger" style={ {display: 'none'} }></div>
                                                                <input type="password" className="form-control" value={ password } minLength="5" maxLength="100" onChange={ (e) => setPassword(e.target.value) } required/>
                                                            </div>
                                                            <div className="col-lg-6 form-group">
                                                                <label>Type Of Service</label>
                                                                <div id="typeServices" className="text-danger" style={ {display: 'none'} }></div>
                                                                <select className="form-control" onChange={ (e) => setTypeServices(e.target.value) }  required>
                                                                    <option value="" style={ {display: 'none'} }>Select</option>
                                                                    <option value="API" selected={ (typeServices == 'API' ? 'selected' : '' ) }>API</option>
                                                                    <option value="CSV" selected={ (typeServices == 'CSV' ? 'selected' : '' ) }>CSV</option>
                                                                </select>
                                                            </div>
                                                            <div className="col-lg-6 form-group">
                                                                <label>Scan Length</label>
                                                                <div id="length_field" className="text-danger" style={ {display: 'none'} }></div>
                                                                <input type="number" className="form-control" value={ lengthField } max="50" onChange={ (e) => setLengthField(e.target.value) } required/>
                                                            </div>
                                                            <div className="col-lg-6 form-group">
                                                                <label>Status</label>
                                                                <div id="status" className="text-danger" style={ {display: 'none'} }></div>
                                                                <select className="form-control" onChange={ (e) => setStatus(e.target.value) }  required>
                                                                    <option value="" style={ {display: 'none'} }>Select</option>
                                                                    <option value="Active" selected={ (status == 'Active' ? 'selected' : '' ) }>Active</option>
                                                                    <option value="Inactive" selected={ (status == 'Inactive' ? 'selected' : '' ) }>Inactive</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div className="row">
                                                            <div className="col-lg-12 form-group" style={ {display: (id == 0 || typeServices == 'CSV' ? 'none' : 'block')} }>
                                                                <label>KEY WEBHOOK</label>
                                                                <input type="text" className="form-control" value={ keyWebhook } maxLength="100" onChange={ (e) => setKeyWebhook(e.target.value) }/>
                                                            </div>
                                                            <div className="col-lg-12 form-group" style={ {display: (id == 0 || typeServices == 'CSV' ? 'none' : 'block')} }>
                                                                <label>URL WEBHOOK</label>
                                                                <input type="text" className="form-control" value={ urlWebhook } maxLength="100" onChange={ (e) => setUrlWebhook(e.target.value) }/>
                                                            </div>
                                                            <div className="col-lg-6 form-group" style={ {display: (id == 0 ? 'none' : 'block')} }>
                                                                <label>On Hold</label>
                                                                <input type="text" className="form-control" value={ onHold } maxLength="100" onChange={ (e) => setOnHold(e.target.value) }/>
                                                            </div>
                                                            <div className="col-lg-6 form-group" style={ {display: (id == 0 ? 'none' : 'block')} }>
                                                                <label>Inbound</label>
                                                                <input type="text" className="form-control" value={ inbound } maxLength="100" onChange={ (e) => setInbound(e.target.value) }/>
                                                            </div>
                                                            <div className="col-lg-6 form-group" style={ {display: (id == 0 ? 'none' : 'block')} }>
                                                                <label>Dispatch</label>
                                                                <input type="text" className="form-control" value={ dispatch } maxLength="100" onChange={ (e) => setDispatch(e.target.value) }/>
                                                            </div>
                                                            <div className="col-lg-6 form-group" style={ {display: (id == 0 ? 'none' : 'block')} }>
                                                                <label>Delivery</label>
                                                                <input type="text" className="form-control" value={ delivery } maxLength="100" onChange={ (e) => setDelivery(e.target.value) }/>
                                                            </div>
                                                            <div className="col-lg-6 form-group" style={ {display: (id == 0 ? 'none' : 'block')} }>
                                                                <label>Re-Inbound</label>
                                                                <input type="text" className="form-control" value={ reInbound } maxLength="100" onChange={ (e) => setReInbound(e.target.value) }/>
                                                            </div>
                                                            <div className="col-lg-6 form-group" style={ {display: (id == 0 ? 'none' : 'block')} }>
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
            { modalBaseRates }
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
                                <div className="col-lg-12 table-responsive">
                                    <table className="table table-hover table-condensed table-bordered">
                                        <thead>
                                            <tr>
                                                <th>COMPANY NAME</th>
                                                <th>EMAIL</th>
                                                <th>KEY API</th>
                                                <th>KEY WEBHOOK</th>
                                                <th>URL WEBHOOK</th>
                                                <th>TYPE SERVICES</th>
                                                <th>STATUS CODE</th>
                                                <th>STATUS</th>
                                                <th style={ {display: 'none'} }>CONFIGURATION</th>
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