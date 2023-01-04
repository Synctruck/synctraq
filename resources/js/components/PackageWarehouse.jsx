import React, { useState, useEffect } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'
import Select from 'react-select'
import moment from 'moment/moment'
import ReactLoading from 'react-loading';

function PackageWarehouse() {

    const [listPackageInbound, setListPackageInbound] = useState([]);
    const [listPackageTotal, setListPackageTotal]     = useState([]);
    const [listStateValidate , setListStateValidate]  = useState([]);
    const [listState , setListState]                  = useState([]);
    const [listValidator , setListValidator]          = useState([]);
    const [listCompany , setListCompany]              = useState([]);

    const [listRoute, setListRoute]     = useState([]);

    const [quantityWarehouse, setQuantityWarehouse] = useState(0);

    const [Reference_Number_1, setNumberPackage] = useState('');
    const [Truck, setTruck]                      = useState('');
    const [Client, setClient]                    = useState('');
    const [idCompany, setCompany]                = useState(0);

    const [textMessage, setTextMessage]         = useState('');
    const [textMessage2, setTextMessage2]       = useState('');
    const [textMessageDate, setTextMessageDate] = useState('');
    const [typeMessage, setTypeMessage]         = useState('');

    const [idValidator, setIdValidator] = useState(0);
    const [dateStart, setDateStart]     = useState(auxDateInit);
    const [dateEnd, setDateEnd]         = useState(auxDateInit);

    const [listInbound, setListInbound] = useState([]);

    const [file, setFile]             = useState('');

    const [displayButton, setDisplayButton] = useState('none');

    const [disabledInput, setDisabledInput] = useState(false);
    const [isLoading, setIsLoading]         = useState(false);
    const [readInput, setReadInput]         = useState(false);

    var dateNow = new Date();
    const day = (dateNow.getDate()) < 10 ? '0'+dateNow.getDate():dateNow.getDate() 
    const month = (dateNow.getMonth() +1) < 10 ? '0'+(dateNow.getMonth() +1):(dateNow.getMonth() +1)

    dateNow = dateNow.getFullYear()+ "-" + month + "-" + day;
    const [filterDate, setFilterDate] = useState(dateNow);

    const [page, setPage]                 = useState(1);
    const [totalPage, setTotalPage]       = useState(0);
    const [totalPackage, setTotalPackage] = useState(0);

    const inputFileRef  = React.useRef();

    const [viewButtonSave, setViewButtonSave] = useState('none');

    document.getElementById('bodyAdmin').style.backgroundColor = '#fff3cd';

    useEffect(() => {

        listAllRoute();
        listAllValidator();
        listAllCompany();

        document.getElementById('Reference_Number_1').focus();

    }, []);

    useEffect(() => {

        listAllPackageWarehouse(page, RouteSearch, StateSearch);

    }, [ idCompany, idValidator, dateStart, dateEnd ]);

    useEffect(() => {

    }, [Reference_Number_1])

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

    const listAllPackageWarehouse = (pageNumber, route, state) => {

        setIsLoading(true);
        setOptionsStateValidate([]);

        fetch(url_general +'package-warehouse/list/'+ idCompany +'/'+ idValidator +'/'+ dateStart+'/'+ dateEnd +'/'+ route +'/'+ state +'/?page='+ pageNumber)
        .then(res => res.json())
        .then((response) => {

            setIsLoading(false);
            setListPackageInbound(response.packageList.data);
            setTotalPackage(response.packageList.total);
            setTotalPage(response.packageList.per_page);
            setPage(response.packageList.current_page);
            setQuantityWarehouse(response.quantityWarehouse);
            setListStateValidate(response.listStateValidate);

            listOptionStateValidate(response.listStateValidate);
        });
    }

    const handlerChangePage = (pageNumber) => {

        listAllPackageWarehouse(pageNumber, RouteSearch, StateSearch);
    }

    const listAllCompany = () => {

        setListCompany([]);

        fetch(url_general +'company/getAll')
        .then(res => res.json())
        .then((response) => {

            setListCompany([{id:0,name:"ALL"},...response.companyList]);
        });
    }

    const listAllRoute = () => {

        setListRoute([]);

        fetch(url_general +'routes/filter/list')
        .then(res => res.json())
        .then((response) => {

            setListState(response.listState);
            listOptionState(response.listState);

            setListRoute(response.listRoute);
            listOptionRoute(response.listRoute);
        });
    }

    const listAllValidator = () => {

        setListValidator([]);

        fetch(url_general +'validator/warehouse/getAll')
        .then(res => res.json())
        .then((response) => {

            console.log(response);
            setListValidator(response.validatorList);
        });
    }

    const [Reference_Number_1_Edit, setReference_Number_1] = useState('');
    const [Dropoff_Contact_Name, setDropoff_Contact_Name] = useState('');
    const [Dropoff_Contact_Phone_Number, setDropoff_Contact_Phone_Number] = useState('');
    const [Dropoff_Address_Line_1, setDropoff_Address_Line_1] = useState('');
    const [Dropoff_Address_Line_2, setDropoff_Address_Line_2] = useState('');
    const [Dropoff_City, setDropoff_City] = useState('');
    const [Dropoff_Province, setDropoff_Province] = useState('');
    const [Dropoff_Postal_Code, setDropoff_Postal_Code] = useState('');
    const [Weight, setWeight] = useState('');
    const [Route, setRoute] = useState('');
    const [RouteSearch, setRouteSearch] = useState('all');
    const [StateSearch, setStateSearch] = useState('all');
    const [StateValidate, setStateValidate] = useState('');

    const [readOnlyInput, setReadOnlyInput]   = useState(false);
    const [disabledButton, setDisabledButton] = useState(false);

    const [textButtonSave, setTextButtonSave] = useState('Guardar');

    const optionsRole = listRoute.map( (route, i) => {

        return (

            <option key={ i } value={ route.name } selected={ Route == route.name ? true : false }> {route.name}</option>
        );
    });

    const handlerOpenModal = (PACKAGE_ID) => {

        fetch(url_general +'package-warehouse/get/'+ PACKAGE_ID)
        .then(res => res.json())
        .then((response) => {

            setReference_Number_1(PACKAGE_ID);
            setDropoff_Contact_Name(response.package.Dropoff_Contact_Name);
            setDropoff_Contact_Phone_Number(response.package.Dropoff_Contact_Phone_Number);
            setDropoff_Address_Line_1(response.package.Dropoff_Address_Line_1);
            setDropoff_Address_Line_2((response.package.Dropoff_Address_Line_2 ? response.package.Dropoff_Address_Line_2 : ''));
            setDropoff_City(response.package.Dropoff_City);
            setDropoff_Province(response.package.Dropoff_Province);
            setDropoff_Postal_Code(response.package.Dropoff_Postal_Code);
            setWeight(response.package.Weight);
            setRoute(response.package.Route);
        });

        //clearValidation();

        setReadOnlyInput(true);

        let myModal = new bootstrap.Modal(document.getElementById('modalPackageEdit'), {

            keyboard: true
        });

        myModal.show();
    }

    const handlerUpdatePackage = (e) => {

        e.preventDefault();

        const formData = new FormData();

        formData.append('Reference_Number_1', Reference_Number_1_Edit);
        formData.append('Dropoff_Contact_Name', Dropoff_Contact_Name);
        formData.append('Dropoff_Contact_Phone_Number', Dropoff_Contact_Phone_Number);
        formData.append('Dropoff_Address_Line_1', Dropoff_Address_Line_1);
        formData.append('Dropoff_Address_Line_2', Dropoff_Address_Line_2);
        formData.append('Dropoff_City', Dropoff_City);
        formData.append('Dropoff_Province', Dropoff_Province);
        formData.append('Dropoff_Postal_Code', Dropoff_Postal_Code);
        formData.append('Weight', Weight);
        formData.append('Route', Route);
        formData.append('status', true);

        clearValidation();

        let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        setDisabledButton(true);
        setTextButtonSave('Loading...');

        let url = 'package-warehouse/update'

        fetch(url_general + url, {
            headers: { "X-CSRF-TOKEN": token },
            method: 'post',
            body: formData
        })
        .then(res => res.json()).
        then((response) => {

                setTextButtonSave('Guardar');
                setDisabledButton(false);

                if(response.stateAction)
                {
                    swal('Se actualizó el Package!', {

                        icon: "success",
                    });

                    listAllPackageWarehouse(1, RouteSearch, StateSearch);
                }
                else(response.status == 422)
                {
                    for(const index in response.errors)
                    {
                        document.getElementById(index).style.display = 'block';
                        document.getElementById(index).innerHTML     = response.errors[index][0];
                    }
                }
            },
        );
    }

    const clearValidation = () => {

        document.getElementById('Reference_Number_1_Edit').style.display = 'none';
        document.getElementById('Reference_Number_1_Edit').innerHTML     = '';

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
        setDropoff_Address_Line_2('');
        setDropoff_City('');
        setDropoff_Province('');
        setDropoff_Postal_Code('');
        setWeight('');
        setRoute(0);
    }

    const modalPackageEdit = <React.Fragment>
                                    <div className="modal fade" id="modalPackageEdit" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div className="modal-dialog">
                                            <form onSubmit={ handlerUpdatePackage }>
                                                <div className="modal-content">
                                                    <div className="modal-header">
                                                        <h5 className="modal-title text-primary" id="exampleModalLabel">Update Package</h5>
                                                        <button type="button" className="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div className="modal-body">
                                                        <div className="row">
                                                            <div className="col-lg-6">
                                                                <div className="form-group">
                                                                    <label>PACKAGE ID</label>
                                                                    <div id="Reference_Number_1_Edit" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="text" value={ Reference_Number_1_Edit } className="form-control" onChange={ (e) => setReference_Number_1(e.target.value) } maxLength="24" readOnly={ readOnlyInput } required/>
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
                                                            <div className="col-lg-12">
                                                                <div className="form-group">
                                                                    <label>CONTACT</label>
                                                                    <div id="Dropoff_Contact_Phone_Number" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="text" value={ Dropoff_Contact_Phone_Number } className="form-control" onChange={ (e) => setDropoff_Contact_Phone_Number(e.target.value) } required/>
                                                                </div>
                                                            </div>
                                                            <div className="col-lg-6">
                                                                <div className="form-group">
                                                                    <label>ADDRESS 1</label>
                                                                    <div id="Dropoff_Address_Line_1" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="text" value={ Dropoff_Address_Line_1 } className="form-control" onChange={ (e) => setDropoff_Address_Line_1(e.target.value) } required/>
                                                                </div>
                                                            </div>
                                                            <div className="col-lg-6">
                                                                <div className="form-group">
                                                                    <label>ADDRESS 2</label>
                                                                    <div id="Dropoff_Address_Line_1" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="text" value={ Dropoff_Address_Line_2 } className="form-control" onChange={ (e) => setDropoff_Address_Line_2(e.target.value) } required/>
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
                                                                    <select name="" id="" className="form-control" onChange={ (e) => setRoute(e.target.value) } required>
                                                                        <option value="" style={ {display: 'none'} }>Seleccione una ruta</option>
                                                                        { optionsRole }
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div className="modal-footer">
                                                        <button type="button" className="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                                        <button className="btn btn-primary" disabled={ disabledButton }>Actualizar</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </React.Fragment>;

    const [sendWarehouse, setSendWarehouse] = useState(1);

    const handlerInsert = (e) => {

        e.preventDefault();

        console.log(sendWarehouse);

        if(sendWarehouse)
        {
            const formData = new FormData();

            formData.append('Reference_Number_1', Reference_Number_1);
            formData.append('StateValidate', StateValidate);

            let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            setReadInput(true);
            setSendWarehouse(0);
            setIsLoading(true);

            fetch(url_general +'package-warehouse/insert', {
                headers: { "X-CSRF-TOKEN": token },
                method: 'post',
                body: formData
            })
            .then(res => res.json())
            .then((response) => {

                    setIsLoading(false);
                    setTextMessageDate('');
                    setTextMessage2('');

                    if(response.stateAction == 'validatedFilterPackage')
                    {
                        let packageBlocked  = response.packageBlocked;
                        let packageManifest = response.packageManifest;

                        if(packageBlocked)
                        {
                            Swal.fire({
                                icon: 'error',
                                title: 'PACKAGE BLOCKED #'+ Reference_Number_1,
                                text: packageBlocked.comment,
                                showConfirmButton: false,
                                timer: 2000,
                            });
                        }

                        //setTextMessage(" LABEL #"+ Reference_Number_1);

                        //setTextMessage(" LABEL #"+ Reference_Number_1);


                        setTypeMessage('primary');
                        setNumberPackage('');

                        document.getElementById('soundPitidoBlocked').play();
                    }
                    else if(response.stateAction == 'validatedReturnCompany')
                    {
                        setTextMessage("The package was registered before for return to the company #"+ Reference_Number_1);
                    }
                    else if(response.stateAction == 'packageInPreDispatch')
                    {
                        setTextMessage('The package is in  PRE DISPATCH #'+ Reference_Number_1);
                        setTypeMessage('warning');
                        setNumberPackage('');

                        document.getElementById('soundPitidoWarning').play();
                    }
                    else if(response.stateAction == 'nonValidatedState')
                    {
                        setTextMessage("#"+ Reference_Number_1 +' / '+ response.packageWarehouse.Dropoff_Province +' / '+ response.packageWarehouse.Route);
                        setTypeMessage('error');
                        setNumberPackage('');

                        document.getElementById('soundPitidoError').play();
                    }
                    else if(response.stateAction == 'countValidations')
                    {
                        setTextMessage("You cannot validate the same package more than 2 times a day #"+ Reference_Number_1 +' / '+ response.packageWarehouse.Dropoff_Province +' / '+ response.packageWarehouse.Route);
                        setTypeMessage('error');
                        setNumberPackage('');

                        document.getElementById('soundPitidoError').play();
                    }
                    else if(response.stateAction == 'notExists')
                    {
                        setTextMessage("NO INBOUND and NO DISPATCH #"+ Reference_Number_1);
                        setTypeMessage('error');
                        setNumberPackage('');

                        document.getElementById('soundPitidoError').play();
                    }
                    else if(response.stateAction == 'packageInWarehouse')
                    {
                        let packageWarehouse = response.packageWarehouse;

                        setTextMessage("WAREHOUSE TODAY:  #"+ Reference_Number_1 +' / '+ packageWarehouse.Route);
                        setTextMessageDate(packageWarehouse.created_at);
                        setTypeMessage('warning');
                        setNumberPackage('');

                        document.getElementById('soundPitidoWarning').play();
                    }
                    else if(response.stateAction == 'packageUpdateCreatedAt')
                    {
                        let packageWarehouse = response.packageWarehouse;

                        setTextMessage("WAREHOUSE UPDATE TODAY:  #"+ Reference_Number_1 +' / '+ packageWarehouse.Route);
                        setTextMessageDate(packageWarehouse.created_at);
                        setTypeMessage('success');
                        setNumberPackage('');

                        document.getElementById('soundPitidoSuccess').play();
                    }
                    else if(response.stateAction)
                    {
                        setTextMessage("VALID WAREHOUSE / "+ Reference_Number_1 +' / '+ response.packageWarehouse.Dropoff_Province +' / '+ response.packageWarehouse.Route);
                        setTypeMessage('success');
                        setNumberPackage('');

                        document.getElementById('Reference_Number_1').focus();
                        document.getElementById('soundPitidoSuccess').play();
                    }

                    listAllPackageWarehouse(1, RouteSearch, StateSearch);

                    setReadInput(false);
                    setSendWarehouse(1);
                },
            );
        }
    }

    const handlerImport = (e) => {

        e.preventDefault();

        const formData = new FormData();

        formData.append('file', file);

        let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        LoadingShow();

        fetch(url_general +'package-warehouse/import', {
            headers: { "X-CSRF-TOKEN": token },
            method: 'post',
            body: formData
        })
        .then(res => res.json()).
        then((response) => {

                if(response.stateAction)
                {
                    swal("Se importó el archivo!", {

                        icon: "success",
                    });

                    document.getElementById('fileImport').value = '';

                    listAllPackageWarehouse(page, RouteSearch, StateSearch);

                    setViewButtonSave('none');
                }

                LoadingHide();
            },
        );
    }

    const handlerViewPDF = (Reference_Number) => {

        window.open(url_general +'package-warehouse/pdf-label/'+ Reference_Number);
    }

    const listPackageTable = listPackageInbound.map( (pack, i) => {

        return (

            <tr key={i} className="alert-success">
                <td>
                    { pack.created_at.substring(5, 7) }-{ pack.created_at.substring(8, 10) }-{ pack.created_at.substring(0, 4) }
                </td>
                <td>
                    { pack.created_at.substring(11, 19) }
                </td>
                <td><b>{ pack.company }</b></td>
                <td><b>{ pack.user.name +' '+ pack.user.nameOfOwner }</b></td>
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
                    <button className="btn btn-primary btn-sm" onClick={ () => handlerOpenModal(pack.Reference_Number_1) } style={ {margin: '3px'}}>
                        <i className="bx bx-edit-alt"></i>
                    </button>

                    <button className="btn btn-success btn-sm" onClick={ () => handlerViewPDF(pack.Reference_Number_1) }>
                        PDF
                    </button>
                </td>
            </tr>
        );
    });

    const exportAllPackageWarehouse = (route, state) => {

        location.href = url_general +'package-warehouse/export/'+ idCompany +'/'+ idValidator +'/'+ dateStart+'/'+ dateEnd +'/'+ route +'/'+ state
    }

    const handlerExport = () => {

        // let date1= moment(dateStart);
        // let date2 = moment(dateEnd);
        // let difference = date2.diff(date1,'days');

        // if(difference> limitToExport){
        //     swal(`Maximum limit to export is ${limitToExport} days`, {
        //         icon: "warning",
        //     });
        // }else{

       // }
       exportAllPackageWarehouse(RouteSearch, StateSearch);
    }

    const handlerChangeRoute = (routes) => {

        if(routes.length != 0)
        {
            let routesSearch = '';

            routes.map( (route) => {

                routesSearch = routesSearch == '' ? route.value : routesSearch +','+ route.value;
            });

            setRouteSearch(routesSearch);

            listAllPackageWarehouse(page, routesSearch, StateSearch);
        }
        else
        {
            setRouteSearch('all');

            listAllPackageWarehouse(page, 'all', StateSearch);
        }
    };

    const [optionsRoleSearch, setOptionsRoleSearch] = useState([]);

    const listOptionRoute = (listRoutes) => {

        setOptionsRoleSearch([]);

        listRoutes.map( (route, i) => {

            optionsRoleSearch.push({ value: route.name, label: route.name });

            setOptionsRoleSearch(optionsRoleSearch);
        });
    }

    const handlerChangeState = (states) => {

        if(states.length != 0)
        {
            let statesSearch = '';

            states.map( (state) => {

                statesSearch = statesSearch == '' ? state.value : statesSearch +','+ state.value;
            });

            setStateSearch(statesSearch);

            listAllPackageWarehouse(page, RouteSearch, statesSearch);
        }
        else
        {
            setStateSearch('all');

            listAllPackageWarehouse(page, RouteSearch, 'all');
        }
    };

    const handlerChangeStateValidate = (states) => {

        if(states.length != 0)
        {
            let statesSearchValidate = '';

            states.map( (state) => {

                statesSearchValidate = statesSearchValidate == '' ? state.value : statesSearchValidate +','+ state.value;
            });

            setStateValidate(statesSearchValidate);
        }
        else
        {
            setStateValidate('');
        }
    };

    const [optionsStateSearch, setOptionsStateSearch]     = useState([]);
    const [optionsStateValidate, setOptionsStateValidate] = useState([]);

    const listOptionState = (listState) => {

        setOptionsStateSearch([]);

        listState.map( (state, i) => {

            optionsStateSearch.push({ value: state.state, label: state.state });

            setOptionsStateSearch(optionsStateSearch);
        });
    }

    const listOptionStateValidate = (listState) => {

        setOptionsStateValidate([]);

        listState.map( (state, i) => {

            optionsStateValidate.push({ value: state.name, label: state.name });

            setOptionsStateValidate(optionsStateValidate);
        });
    }

    const optionValidator = listValidator.map( (validator, i) => {

        return <option value={ validator.id }>{ validator.name +' '+ validator.nameOfOwner }</option>
    })

    const onBtnClickFile = () => {

        setViewButtonSave('none');

        inputFileRef.current.click();
    }

    const [EWR1, setEWR1]                     = useState('EWR1');
    const [WeightLabel, setWeightLabel]       = useState('12');
    const [StateLabel, setStateLabel]         = useState('CR');
    const [ReferenceLabel, setReferenceLabel] = useState('');
    const [RouteLabel, setRouteLabel]         = useState('QWE');

    const handlerPrint = (nombreDiv) => {

        JsBarcode("#imgBarcode", Reference_Number_1, {

            textMargin: 0,
            fontSize: 27,
        });

        var content = document.getElementById('labelPrint');
        var pri     = document.getElementById('ifmcontentstoprint').contentWindow;

        pri.document.open();
        pri.document.write(content.innerHTML);
        pri.document.close();
        pri.focus();
        pri.print();

        document.getElementById('Reference_Number_1').focus();
    }

    const optionCompany = listCompany.map( (company, i) => {

        return <option value={company.id}>{company.name}</option>
    })

    return (

        <section className="section">
            { modalPackageEdit }
            <div className="row">
                <div className="col-lg-12">
                    <div className="card">
                        <div className="card-body">
                            <h5 className="card-title">
                                <div className="row form-group">
                                    <div className="col-12 mb-4">
                                        <div className="row">
                                            <div className="col-2">
                                                <button className="btn btn-success btn-sm form-control" onClick={  () => handlerExport() }>
                                                    <i className="ri-file-excel-fill"></i> EXPORT
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div className="col-lg-12 form-group text-center">
                                        {
                                            typeMessage == 'success'
                                            ?
                                                <h2 className="text-success">{ textMessage }</h2>

                                            :
                                                ''
                                        }
                                        {
                                            typeMessage == 'error'
                                            ?
                                                <h2 className="text-danger">{ textMessage }</h2>
                                            :
                                                ''
                                        }
                                        {
                                            typeMessage == 'primary'
                                            ?
                                                <h2 className="text-primary">{ textMessage }</h2>
                                            :
                                                ''
                                        }
                                        {
                                            typeMessage == 'warning'
                                            ?
                                                <h2 className="text-warning">{ textMessage }</h2>
                                            :
                                                ''
                                        }

                                        {
                                            textMessageDate != ''
                                            ?
                                                <h2 className="text-warning">{ textMessageDate.substring(5, 7) }-{ textMessageDate.substring(8, 10) }-{ textMessageDate.substring(0, 4) } { textMessageDate.substring(11, 19) }</h2>
                                            :
                                                ''
                                        }
                                    </div>
                                    <div className="col-lg-10 form-group">
                                        <form onSubmit={ handlerInsert } autoComplete="off">
                                            <div className="form-group">
                                                <label htmlFor="">PACKAGE ID</label>
                                                <input id="Reference_Number_1" type="text" className="form-control" value={ Reference_Number_1 } onChange={ (e) => setNumberPackage(e.target.value) } readOnly={ readInput } maxLength="24" required/>
                                            </div>
                                            <div className="col-lg-2 form-group">
                                                <audio id="soundPitidoSuccess" src="./sound/pitido-success.mp3" preload="auto"></audio>
                                                <audio id="soundPitidoError" src="./sound/pitido-error.mp3" preload="auto"></audio>
                                                <audio id="soundPitidoWarning" src="./sound/pitido-warning.mp3" preload="auto"></audio>
                                                <audio id="soundPitidoBlocked" src="./sound/pitido-blocked.mp3" preload="auto"></audio>
                                            </div>
                                        </form>
                                    </div>
                                    <div className="col-lg-2">
                                        <div className="form-group">
                                            <label htmlFor="">LinaHaul Filter</label>
                                            <Select isMulti onChange={ (e) => handlerChangeStateValidate(e) } options={ optionsStateValidate } />
                                        </div>
                                    </div>
                                    <div className="col-lg-2" style={ {display: 'none'} }>
                                        <form onSubmit={ handlerImport }>
                                            <div className="form-group">
                                                <label htmlFor="" style={ {color: 'white'} }>PACKAGE ID</label>
                                                <button type="button" className="btn btn-primary form-control" onClick={ () => onBtnClickFile() }>
                                                    <i className="bx bxs-file"></i> Import
                                                </button>
                                                <input type="file" id="fileImport" className="form-control" ref={ inputFileRef } style={ {display: 'none'} } onChange={ (e) => setFile(e.target.files[0]) } accept=".csv" required/>
                                            </div>
                                            <div className="form-group" style={ {display: viewButtonSave} }>
                                                <button className="btn btn-primary form-control" onClick={ () => handlerImport() }>
                                                    <i className="bx  bxs-save"></i> Save
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                <div className="row">
                                    <div className="col-lg-2 mb-3" style={ {paddingLeft: (isLoading ? '5%' : '')} }>
                                        {
                                            (
                                                isLoading
                                                ? 
                                                    <ReactLoading type="bubbles" color="#A8A8A8" height={20} width={50} />
                                                :
                                                    <b className="alert-success" style={ {borderRadius: '10px', padding: '10px'} }>Warehouse: { totalPackage }</b>
                                            )
                                        }
                                    </div>
                                </div>
                                <div className="row">
                                    <div className="col-lg-2">
                                        <div className="row">
                                            <div className="col-lg-12">
                                                <div className="form-group">
                                                    Start date:
                                                </div>
                                            </div>
                                            <div className="col-lg-12">
                                                <input type="date" className='form-control' value={ dateStart } onChange={ (e) => setDateStart(e.target.value) }/>
                                            </div>
                                        </div>
                                    </div>
                                    <div className="col-lg-2">
                                        <div className="row">
                                            <div className="col-lg-12">
                                                <div className="form-group">
                                                    End date :
                                                </div>
                                            </div>
                                            <div className="col-lg-12">
                                                <input type="date" className='form-control' value={ dateEnd } onChange={ (e) => setDateEnd(e.target.value) }/>
                                            </div>
                                        </div>
                                    </div>
                                    <div className="col-lg-2">
                                        <div className="row">
                                            <div className="col-lg-12">
                                                <div className="form-group">
                                                    Company:
                                                </div>
                                            </div>
                                            <div className="col-lg-12">
                                                <select name="" id="" className="form-control" onChange={ (e) => setCompany(e.target.value) }>
                                                    <option value="" style={ {display: 'none'} }>Select...</option>
                                                    { optionCompany }
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div className="col-lg-2">
                                        <div className="row">
                                            <div className="col-lg-12">
                                                <div className="form-group">
                                                    Validators:
                                                </div>
                                            </div>
                                            <div className="col-lg-12">
                                                <select className="form-control" onChange={ (e) => setIdValidator(e.target.value) }>
                                                    <option value="0">All</option>
                                                    { optionValidator }
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div className="col-lg-2">
                                        <div className="row">
                                            <div className="col-lg-12">
                                                <div className="form-group">
                                                    State :
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
                                                    Route :
                                                </div>
                                            </div>
                                            <div className="col-lg-12">
                                                <Select isMulti onChange={ (e) => handlerChangeRoute(e) } options={ optionsRoleSearch } />
                                            </div>
                                        </div>
                                    </div>
                                    <div className="col-lg-6">
                                    </div>
                                    <div className="col-lg-6">
                                        <iframe id="ifmcontentstoprint" style={{
                                            height: '0px',
                                            width: '100%',
                                            position: 'absolute',
                                            fontFamily: 'Arial, Helvetica, sans-serif',
                                        }}>
                                            <div id="labelPrint">
                                                <table>
                                                    <tr>
                                                        <td className="verticalTextRight" style={ {transform: 'rotate(90deg)'} }>
                                                            <h1 style={ {fontSize: '2rem', fontFamily: 'Arial', marginBottom: '0px', position: 'relative', left: '10px', bottom: '40px'} }><b>{ EWR1 }</b></h1>
                                                        </td>
                                                        <td>
                                                            <table>
                                                                <tr>
                                                                    <td className="text-center">
                                                                        <div style={ {float: 'left', width: '35%', fontFamily: 'Arial', marginBottom: '0px'} }>
                                                                            <h1 style={ {textAlign: 'left', paddingLeft: '5px', fontSize: '1.9rem', fontFamily: 'Arial', marginBottom: '0px'} }><b>{ WeightLabel }</b></h1>
                                                                        </div>
                                                                        <div style={ {float: 'left', width: '30%', fontFamily: 'Arial', marginBottom: '0px', textAlign: 'center'} }>
                                                                            <img src={ 'https://synctrucknj.com/img/logo.PNG' } style={ {width: '115px', left: '-25px', top: '30px', position: 'relative', fontFamily: 'Arial', marginBottom: '0px'} }/>
                                                                        </div>
                                                                        <div style={ {float: 'left', width: '35%', fontFamily: 'Arial', marginBottom: '0px'} }>
                                                                            <h1 style={ {textAlign: 'right', paddingRight: '5px', fontSize: '1.9rem', fontFamily: 'Arial', marginBottom: '0px'} }><b>{ StateLabel }</b></h1>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td style={ {textAlign: 'center'} }>
                                                                        <svg id="imgBarcode" style={ {width: '400', height: '250', margin: '0px'} }></svg>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td className="text-center" style={ {textAlign: 'center', fontFamily: 'Arial', marginBottom: '0px'} }>
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                        <td className="verticalTextRight" style={ {transform: 'rotate(90deg)', fontFamily: 'Arial', marginBottom: '0px'} }>
                                                            <h1 style={ {fontSize: '3.2rem', fontFamily: 'Arial', marginBottom: '0px', position: 'relative', left: '10px', bottom: '-40px'} }><b>{ RouteLabel }</b></h1>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </iframe>
                                    </div>
                                </div>
                            </h5>
                            <div className="row form-group table-responsive">
                                <div className="col-lg-12">
                                    <table className="table table-hover table-condensed table-bordered">
                                        <thead>
                                            <tr>
                                                <th>DATE</th>
                                                <th>HOUR</th>
                                                <th>COMPANY</th>
                                                <th>VALIDATOR</th>
                                                <th>PACKAGE ID</th>
                                                <th>CLIENT</th>
                                                <th>CONTACT</th>
                                                <th>ADDREESS</th>
                                                <th>CITY</th>
                                                <th>STATE</th>
                                                <th>ZIP CODE</th>
                                                <th>WEIGHT</th>
                                                <th>ROUTE</th>
                                                <th style={ {display: 'none'} }>ACTION</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            { listPackageTable }
                                        </tbody>
                                    </table>
                                </div>
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
        </section>
    );
}

export default PackageWarehouse;

// DOM element
if (document.getElementById('packageWarehouse')) {
    ReactDOM.render(<PackageWarehouse />, document.getElementById('packageWarehouse'));
}
