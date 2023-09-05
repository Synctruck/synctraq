import React, { useState, useEffect } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'
import Select from 'react-select'
import Pace from 'react-pace-progress'
import ReactLoading from 'react-loading';

function Package() {

    const [listPackage, setListPackage]  = useState([]);
    const [listRoute, setListRoute]      = useState([]);
    const [listState , setListState]     = useState([]);
    const [listCompany , setListCompany] = useState([]);

    const [quantityPackage , setQuantityPackage] = useState(0);

    const [file, setFile]             = useState('');

    const [titleModal, setTitleModal] = useState('');

    const [textButtonSave, setTextButtonSave]     = useState('Save');
    const [textButtonImport, setTextButtonImport] = useState('Save');

    const [textMessage, setTextMessage] = useState('');
    const [typeMessage, setTypeMessage] = useState('');

    const [readOnlyInput, setReadOnlyInput]   = useState(false);
    const [disabledButton, setDisabledButton] = useState(false);

    const [page, setPage]                 = useState(1);
    const [totalPage, setTotalPage]       = useState(0);
    const [totalPackage, setTotalPackage] = useState(0);

    const [RouteSearch, setRouteSearch] = useState('all');
    const [StateSearch, setStateSearch] = useState('all');
    const [status, setStatus]           = useState('Manifest');
    const [idCompany, setCompany]       = useState(0);

    const inputFileRef  = React.useRef();

    const [viewButtonSave, setViewButtonSave] = useState('none');
    const [isLoading, setIsLoading]           = useState(false);

    document.getElementById('bodyAdmin').style.backgroundColor = '#cff4fc';

    useEffect(() => {

        if(String(file) == 'undefined' || file == '')
        {
            setViewButtonSave('none');
        }
        else
        {
            setViewButtonSave('block');
        }

    }, [file]);

    useEffect(() => {

        listAllRoute();
        listAllCompany();

    }, []);

    useEffect(() => {

        listAllPackage(page, status, RouteSearch, StateSearch);

    }, [status, idCompany]);

    const listAllPackage = (pageNumber, status, route, state) => {

        LoadingShow();
        setIsLoading(true);

        fetch(url_general +'package-manifest/list/'+ status +'/'+ idCompany +'/'+ route +'/'+ state +'?page='+ pageNumber)
        .then(res => res.json())
        .then((response) => {

            setListPackage(response.packageList.data);
            setQuantityPackage(response.quantityPackage);
            setTotalPackage(response.packageList.total);
            setTotalPage(response.packageList.per_page);
            setPage(response.packageList.current_page);
            setListState(response.listState);
            setIsLoading(false);

            if(listState.length == 0)
            {
                listOptionState(response.listState);
            }

            LoadingHide();
        });
    }

    const handlerChangePage = (pageNumber) => {

        listAllPackage(pageNumber, status, RouteSearch, StateSearch);
    }

    const listAllRoute = () => {

        setListRoute([]);

        fetch(url_general +'routes-aux/list')
        .then(res => res.json())
        .then((response) => {

            setListRoute(response.listRoute);
            listOptionRoute(response.listRoute);
        });
    }

    const handlerImport = (e) => {

        e.preventDefault();

        const formData = new FormData();

        formData.append('file', file);

        let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        LoadingShow();

        setTextButtonImport('Saving...');
        setDisabledButton(true);
        setIsLoading(true);

        fetch(url_general +'package-manifest/import', {
            headers: { "X-CSRF-TOKEN": token },
            method: 'post',
            body: formData
        }, {timeout: 10000})
        .then(res => res.json()).
        then((response) => {

                setTextButtonImport('Save');
                setDisabledButton(false);
                setIsLoading(false);

                if(response.status == 504)
                {
                    swal("Error al importar el archivo, intente nuevamente!", {

                        icon: "error",
                    });
                }
                else if(response.stateAction)
                {
                    swal("Se importó el archivo!", {

                        icon: "success",
                    });

                    document.getElementById('fileImport').value = '';

                    listAllPackage(page, status, RouteSearch, StateSearch);
                    setViewButtonSave('none');
                }

                LoadingHide();
            },
        );
    }

    const handlerFile = (e) => {

        setFile(e.target.files[0]);
    }

    const [Reference_Number_1, setReference_Number_1] = useState('');
    const [Dropoff_Contact_Name, setDropoff_Contact_Name] = useState('');
    const [Dropoff_Contact_Phone_Number, setDropoff_Contact_Phone_Number] = useState('');
    const [Dropoff_Address_Line_1, setDropoff_Address_Line_1] = useState('');
    const [Dropoff_City, setDropoff_City] = useState('');
    const [Dropoff_Province, setDropoff_Province] = useState('');
    const [Dropoff_Postal_Code, setDropoff_Postal_Code] = useState('');
    const [Weight, setWeight] = useState('');
    const [Route, setRoute] = useState('');

    const [action, setAction] = useState('');

    const optionsRole = listRoute.map( (route, i) => {

        return (

            <option key={ i } value={ route.name } selected={ Route == route.name ? true : false }> {route.name}</option>
        );
    });

    const handlerOpenModal = (PACKAGE_ID) => {

        if(PACKAGE_ID)
        {
            fetch(url_general +'package-manifest/get/'+ PACKAGE_ID)
            .then(res => res.json())
            .then((response) => {

                setReference_Number_1(PACKAGE_ID);
                setDropoff_Contact_Name(response.package.Dropoff_Contact_Name);
                setDropoff_Contact_Phone_Number(response.package.Dropoff_Contact_Phone_Number);
                setDropoff_Address_Line_1(response.package.Dropoff_Address_Line_1);
                setDropoff_City(response.package.Dropoff_City);
                setDropoff_Province(response.package.Dropoff_Province);
                setDropoff_Postal_Code(response.package.Dropoff_Postal_Code);
                setWeight(response.package.Weight);
                setRoute(response.package.Route);
            });

            clearValidation();
            clearForm();

            setTitleModal('Update Package');
            setTextButtonSave('Update');
            setAction('Update');
            setReadOnlyInput(true);
        }
        else
        {
            clearValidation();
            clearForm();

            setTitleModal('Add Package');
            setTextButtonSave('Save');

            setReference_Number_1('');
            setAction('Save');
            setReadOnlyInput(false);
        }

        let myModal = new bootstrap.Modal(document.getElementById('modalPackageInsert'), {

            keyboard: true
        });

        myModal.show();
    }

    const handlerSavePackage = (e) => {

        e.preventDefault();

        const formData = new FormData();

        formData.append('Reference_Number_1', Reference_Number_1);
        formData.append('Dropoff_Contact_Name', Dropoff_Contact_Name);
        formData.append('Dropoff_Contact_Phone_Number', Dropoff_Contact_Phone_Number);
        formData.append('Dropoff_Address_Line_1', Dropoff_Address_Line_1);
        formData.append('Dropoff_City', Dropoff_City);
        formData.append('Dropoff_Province', Dropoff_Province);
        formData.append('Dropoff_Postal_Code', Dropoff_Postal_Code);
        formData.append('Weight', Weight);
        formData.append('Route', Route);
        formData.append('status', true);

        clearValidation();

        let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        setDisabledButton(true);
        setTextButtonSave((action == 'Update' ? 'Updating...' : 'Saving...'));

        let url = (action == 'Update' ? 'package-manifest/update' : 'package-manifest/insert');

        let messageAction = (action == 'Update' ? 'Package was updated!' : 'Package was registered!');

        fetch(url_general + url, {
            headers: { "X-CSRF-TOKEN": token },
            method: 'post',
            body: formData
        })
        .then(res => res.json()).
        then((response) => {

                setTextButtonSave((action == 'Update' ? 'Update' : 'Save'));
                setDisabledButton(false);

                if(response.stateAction == 'notInland')
                {
                    swal('Atención!', "The package #"+ Reference_Number_1 +" does not have the initials INLAND or 67660!", 'warning');
                }
                else if(response.stateAction == 'exists')
                {
                    swal('Atención!', "The package #"+ Reference_Number_1 +" it already exists!", 'warning');
                }
                else if(response.stateAction == true)
                {
                    swal(messageAction, {

                        icon: "success",
                    });

                    if(action != 'Update')
                    {
                        clearForm();
                    }

                    listAllPackage(page, status, RouteSearch, StateSearch);
                }
                else if(response.status == 422)
                {
                    for(const index in response.errors)
                    {
                        document.getElementById(index).style.display = 'block';
                        document.getElementById(index).innerHTML     = response.errors[index][0];
                    }
                }
                else
                {
                    swal('Atención!', "Hubo un problema, intente nuevamente!", 'warning');
                }
            },
        );
    }

    const clearValidation = () => {

        document.getElementById('Reference_Number_1').style.display = 'none';
        document.getElementById('Reference_Number_1').innerHTML     = '';

        document.getElementById('Dropoff_Contact_Name').style.display = 'none';
        document.getElementById('Dropoff_Contact_Name').innerHTML     = '';

        document.getElementById('Dropoff_Contact_Phone_Number').style.display = 'none';
        document.getElementById('Dropoff_Contact_Phone_Number').innerHTML     = '';

        document.getElementById('Dropoff_Address_Line_1').style.display = 'none';
        document.getElementById('Dropoff_Address_Line_1').innerHTML     = '';

        document.getElementById('Dropoff_City').style.display = 'none';
        document.getElementById('Dropoff_City').innerHTML     = '';

        document.getElementById('Dropoff_Province').style.display = 'none';
        document.getElementById('Dropoff_Province').innerHTML     = '';

        document.getElementById('Dropoff_Postal_Code').style.display = 'none';
        document.getElementById('Dropoff_Postal_Code').innerHTML     = '';

        document.getElementById('Weight').style.display = 'none';
        document.getElementById('Weight').innerHTML     = '';

        document.getElementById('Route').style.display = 'none';
        document.getElementById('Route').innerHTML     = '';
    }

    const clearForm = () => {

        setReference_Number_1('');
        setDropoff_Contact_Name('');
        setDropoff_Contact_Phone_Number('');
        setDropoff_Address_Line_1('');
        setDropoff_City('');
        setDropoff_Province('');
        setDropoff_Postal_Code('');
        setWeight('');
        setRoute('');
    }

    const handlerChangeRoute = (routes) => {

        setPage(1);

        if(routes.length != 0)
        {
            let routesSearch = '';

            routes.map( (route) => {

                routesSearch = routesSearch == '' ? route.value : routesSearch +','+ route.value;
            });

            setRouteSearch(routesSearch);

            listAllPackage(1, status, routesSearch, StateSearch);
        }
        else
        {
            setRouteSearch('all');

            listAllPackage(1, status, 'all', StateSearch);
        }
    };

    const [optionsRoleSearch, setOptionsRoleSearch] = useState([]);

    const listOptionRoute = (listRoutes) => {

        setOptionsRoleSearch([]);

        listRoutes.map( (route, i) => {

            optionsRoleSearch.push({ value: route.name, label: route.name });

            setOptionsRoleSearch(optionsRoleSearch);
        });

        console.log(optionsRoleSearch);
    }

    const handlerChangeState = (states) => {

        setPage(1);

        if(states.length != 0)
        {
            let statesSearch = '';

            states.map( (state) => {

                statesSearch = statesSearch == '' ? state.value : statesSearch +','+ state.value;
            });

            setStateSearch(statesSearch);

            listAllPackage(1, status, RouteSearch, statesSearch);
        }
        else
        {
            setStateSearch('all');

            listAllPackage(1, status, RouteSearch, 'all');
        }
    };

    const [optionsStateSearch, setOptionsStateSearch] = useState([]);

    const listOptionState = (listState) => {

        setOptionsStateSearch([]);

        listState.map( (state, i) => {

            optionsStateSearch.push({ value: state.Dropoff_Province, label: state.Dropoff_Province });

            setOptionsStateSearch(optionsStateSearch);
        });
    }

    const listAllCompany = () => {

        setListCompany([]);

        fetch(url_general +'company/getAll')
        .then(res => res.json())
        .then((response) => {

            setListCompany([{id:0,name:"ALL"},...response.companyList]);
        });
    }

    const optionCompany = listCompany.map( (company, i) => {

        return <option value={company.id}>{company.name}</option>
    });

    const modalPackageInsert = <React.Fragment>
                                    <div className="modal fade" id="modalPackageInsert" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div className="modal-dialog">
                                            <form onSubmit={ handlerSavePackage }>
                                                <div className="modal-content">
                                                    <div className="modal-header">
                                                        <h5 className="modal-title text-primary" id="exampleModalLabel">{ titleModal }</h5>
                                                        <button type="button" className="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div className="modal-body">
                                                        <div className="row">
                                                            <div className="col-lg-6">
                                                                <div className="form-group">
                                                                    <label>PACKAGE ID</label>
                                                                    <div id="Reference_Number_1" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="text" value={ Reference_Number_1 } className="form-control" onChange={ (e) => setReference_Number_1(e.target.value) } maxLength="15" readOnly={ readOnlyInput } required/>
                                                                </div>
                                                            </div>
                                                            <div className="col-lg-6">
                                                                <div className="form-group">
                                                                    <label>CLIENT</label>
                                                                    <div id="Dropoff_Contact_Name" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="text" value={ Dropoff_Contact_Name } className="form-control" onChange={ (e) => setDropoff_Contact_Name(e.target.value) } required/>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div className="row">
                                                            <div className="col-lg-6">
                                                                <div className="form-group">
                                                                    <label>CONTACT</label>
                                                                    <div id="Dropoff_Contact_Phone_Number" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="text" value={ Dropoff_Contact_Phone_Number } className="form-control" onChange={ (e) => setDropoff_Contact_Phone_Number(e.target.value) } required/>
                                                                </div>
                                                            </div>
                                                            <div className="col-lg-6">
                                                                <div className="form-group">
                                                                    <label>ADDRESS</label>
                                                                    <div id="Dropoff_Address_Line_1" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="text" value={ Dropoff_Address_Line_1 } className="form-control" onChange={ (e) => setDropoff_Address_Line_1(e.target.value) } required/>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div className="row">
                                                            <div className="col-lg-6">
                                                                <div className="form-group">
                                                                    <label>CITY</label>
                                                                    <div id="Dropoff_City" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="text" value={ Dropoff_City } className="form-control" onChange={ (e) => setDropoff_City(e.target.value) } required/>
                                                                </div>
                                                            </div>
                                                            <div className="col-lg-6">
                                                                <div className="form-group">
                                                                    <label>STATE</label>
                                                                    <div id="Dropoff_Province" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="text" value={ Dropoff_Province } className="form-control" onChange={ (e) => setDropoff_Province(e.target.value) } required/>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div className="row">
                                                            <div className="col-lg-6">
                                                                <div className="form-group">
                                                                    <label>ZIP C</label>
                                                                    <div id="Dropoff_Postal_Code" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="text" value={ Dropoff_Postal_Code } className="form-control" onChange={ (e) => setDropoff_Postal_Code(e.target.value) } required/>
                                                                </div>
                                                            </div>
                                                            <div className="col-lg-6">
                                                                <div className="form-group">
                                                                    <label>WEIGHT</label>
                                                                    <div id="Weight" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="text" value={ Weight } className="form-control" onChange={ (e) => setWeight(e.target.value) } required/>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div className="row">
                                                            <div className="col-lg-6">
                                                                <div className="form-group">
                                                                    <label>ROUTE</label>
                                                                    <div id="Route" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="text" value={ Route } className="form-control" onChange={ (e) => setRoute(e.target.value) }/>
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

    const listPackageTable = listPackage.map( (pack, i) => {

        return (

            <tr key={i}>
                <td>
                    { pack.created_at.substring(5, 7) }-{ pack.created_at.substring(8, 10) }-{ pack.created_at.substring(0, 4) }
                </td>
                <td>
                    { pack.created_at.substring(11, 19) }
                </td>
                <td>{ pack.company }</td>
                <td><b>{ pack.Reference_Number_1 }</b></td>
                <td>{ pack.Dropoff_Contact_Name }</td>
                <td>{ pack.Dropoff_Contact_Phone_Number }</td>
                <td>{ pack.Dropoff_Address_Line_1 }</td>
                <td>{ pack.Dropoff_City }</td>
                <td>{ pack.Dropoff_Province }</td>
                <td>{ pack.Dropoff_Postal_Code }</td>
                <td>{ pack.Weight }</td>
                <td>{ pack.Route }</td>
                <td style={ {display: 'none'} }>
                    <button className="btn btn-primary btn-sm" onClick={ () => handlerOpenModal(pack.Reference_Number_1) }>
                        <i className="bx bx-edit-alt"></i>
                    </button>
                </td>
            </tr>
        );
    });

    const exportAllPackageInbound = (route, state, type) => {

        let url = url_general +'package-manifest/export/'+ status +'/'+ idCompany +'/'+ route +'/'+ state +'/'+ type;

        if(type == 'download')
        {
            location.href = url;
        }
        else
        {
            setIsLoading(true);

            fetch(url)
            .then(res => res.json())
            .then((response) => {

                if(response.stateAction == true)
                {
                    swal("The export was sended to your mail!", {

                        icon: "success",
                    });
                }
                else
                {
                    swal("There was an error, try again!", {

                        icon: "error",
                    });
                }

                setIsLoading(false);
            });
        }
    }

    const handlerExport = (type) => {

        exportAllPackageInbound(RouteSearch, StateSearch, type);
    }

    const onBtnClickFile = () => {

        setViewButtonSave('none');

        inputFileRef.current.click();
    }

    return (

        <section className="section">
            { modalPackageInsert }
            <div className="row">
                <div className="col-lg-12">
                    <div className="card">
                        <div className="card-body">
                            <h5 className="card-title">
                                <div className="row form-group">
                                    <div className="col-lg-2 form-group" style={ { display: 'none'} }>
                                        <button className="btn btn-success pull-right form-control" title="Agregar" onClick={ () => handlerOpenModal(0) }>
                                            <i className="bx bxs-plus-square"></i> Add
                                        </button>
                                    </div>
                                    <div className="col-lg-2 form-group">
                                        <form onSubmit={ handlerImport }>
                                            <div className="form-group">
                                                <button type="button" className="btn btn-primary btn-sm form-control" onClick={ () => onBtnClickFile() }>
                                                    <i className="bx bxs-file"></i> IMPORT
                                                </button>
                                                <input type="file" id="fileImport" className="form-control" ref={ inputFileRef } style={ {display: 'none'} } onChange={ (e) => setFile(e.target.files[0]) } accept=".csv" required/>
                                            </div>
                                            <div className="form-group" style={ {display: viewButtonSave} }>
                                                <button className="btn btn-primary form-control" onClick={ () => handlerImport() } disabled={ disabledButton }>
                                                    <i className="bx  bxs-save"></i> { textButtonImport }
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                    <div className="col-2 mb-2">
                                        <div className="row">
                                            <div className="col-12">
                                                <button className="btn btn-success btn-sm form-control" onClick={  () => handlerExport('download') }>
                                                    <i className="ri-file-excel-fill"></i> EXPORT
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div className="col-3 mb-2">
                                        <div className="row">
                                            <div className="col-12">
                                                <button className="btn btn-warning btn-sm form-control text-white" onClick={  () => handlerExport('send') }>
                                                    <i className="ri-file-excel-fill"></i> EXPORT TO THE MAIL
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div className="col-lg-6">
                                        
                                    </div>
                                    <div className="col-lg-2">
                                        <div className="row mb-3" style={ {display: (isLoading ? 'block' : 'none')} }>
                                            <div className="col-lg-12">
                                                
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div className="row">
                                    <div className="col-lg-2" style={ {paddingLeft: (isLoading ? '5%' : '')} }>
                                        {
                                            (
                                                isLoading
                                                ? 
                                                    <ReactLoading type="bubbles" color="#A8A8A8" height={20} width={50} />
                                                :
                                                    <b className="alert-info" style={ {borderRadius: '10px', padding: '10px'} }>Manifest: { quantityPackage }</b>
                                            )
                                        }
                                    </div>
                                    <dvi className="col-lg-2">
                                        <div className="row">
                                            <div className="col-lg-12">
                                                <div className="form-group">
                                                    STATUS:
                                                </div>
                                            </div>
                                            <div className="col-lg-12">
                                                <select name="" id="" className="form-control" onChange={ (e) => setStatus(e.target.value) }>
                                                    <option value="Manifest">Manifest</option>
                                                    <option value="NeverReceived">Never Received</option>
                                                </select>
                                            </div>
                                        </div>
                                    </dvi>
                                    <dvi className="col-lg-2">
                                        <div className="row">
                                            <div className="col-lg-12">
                                                <div className="form-group">
                                                    COMPANY:
                                                </div>
                                            </div>
                                            <div className="col-lg-12">
                                                <select name="" id="" className="form-control" onChange={ (e) => setCompany(e.target.value) }>
                                                    <option value="" style={ {display: 'none'} }>Select...</option>
                                                    { optionCompany }
                                                </select>
                                            </div>
                                        </div>
                                    </dvi>
                                    <div className="col-lg-2">
                                        <div className="row">
                                            <div className="col-lg-12">
                                                <div className="form-group">
                                                    STATE :
                                                </div>
                                            </div>
                                            <div className="col-lg-12">
                                                <Select isMulti onChange={ (e) => handlerChangeState(e) } options={ optionsStateSearch } />
                                            </div>
                                        </div>
                                    </div>
                                    <div className="col-lg-2">
                                        <div className="row">
                                            <div className="col-lg-12">
                                                <div className="form-group">
                                                    ROUTE :
                                                </div>
                                            </div>
                                            <div className="col-lg-12">
                                                <Select isMulti onChange={ (e) => handlerChangeRoute(e) } options={ optionsRoleSearch } />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </h5>
                            <div className="row form-group table-responsive">
                                <div className="col-lg-12">
                                    <table className="table table-hover table-condensed">
                                        <thead>
                                            <tr>
                                                <th>DATE</th>
                                                <th>HOUR</th>
                                                <th>COMPANY</th>
                                                <th>PACKAGE ID</th>
                                                <th>CLIENT</th>
                                                <th>CONTACT</th>
                                                <th>ADDRESS</th>
                                                <th>CITY</th>
                                                <th>STATE</th>
                                                <th>ZIP CODE</th>
                                                <th>WEIGHT</th>
                                                <th>ROUTE</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            { listPackageTable }
                                        </tbody>
                                    </table>
                                </div>
                                <div className="col-lg-12">
                                    <Pagination
                                        activePage={page}
                                        totalItemsCount={totalPackage}
                                        itemsCountPerPage={totalPage}
                                        onChange={(pageNumber) => handlerChangePage(pageNumber)}
                                        itemClass="page-item"
                                        linkClass="page-link"
                                        firstPageText="First"
                                        lastPageText="Last"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}

export default Package;

// DOM element
if (document.getElementById('package')) {
    ReactDOM.render(<Package />, document.getElementById('package'));
}
