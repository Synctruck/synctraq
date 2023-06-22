import React, { useState, useEffect } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'
import Select from 'react-select'
import moment from 'moment';
import ReactLoading from 'react-loading';

let count = 1;

function PackageDispatch() {

    const [listPackageDispatch, setListPackageDispatch] = useState([]);
    const [listTeam, setListTeam]                       = useState([]);
    const [listTeamNow, setListTeamNow]                 = useState([]);
    const [listTeamNew, setListTeamNew]                 = useState([]);
    const [listDriver, setListDriver]                   = useState([]);
    const [listDriverAssign, setListDriverAssign]       = useState([]);
    const [roleUser, setRoleUser]                       = useState([]);
    const [listRoute, setListRoute]                     = useState([]); 
    const [listRole, setListRole]                       = useState([]);
    const [listState , setListState]                    = useState([]);
    const [listCompany , setListCompany]                = useState([]);

    const [id, setId]                                 = useState(0);
    const [idRole, setIdRole]                         = useState(0);
    const [name, setName]                             = useState('');
    const [nameOfOwner, setNameOfOwner]               = useState('');
    const [address, setAddress]                       = useState('');
    const [phone, setPhone]                           = useState('');
    const [email, setEmail]                           = useState('');
    const [idsRoutes, setIdsRoutes]                   = useState('');
    const [permissionDispatch, setPermissionDispatch] = useState(0);
    const [dayNight, setDayNight]                     = useState('');

    const [readOnly, setReadOnly] = useState(false);
    const [checkAll, setCheckAll] = useState(0);

    const [quantityDispatch, setQuantityDispatch]         = useState(0);
    const [quantityDispatchAll, setQuantityDispatchAll]   = useState(0);
    const [quantityFailed, setQuantityFailed]             = useState(0);
    const [quantityHighPriority, setQuantityHighPriority] = useState(0);

    // const [dataView, setDataView] = useState('today');
    const [dateStart, setDateStart] = useState(auxDateInit);
    const [dateEnd, setDateEnd]   = useState(auxDateInit);
    const [Reference_Number_1, setNumberPackage] = useState('');
    const [idTeam, setIdTeam] = useState(0);
    const [idTeamNow, setIdTeamNow] = useState(0);
    const [idTeamNew, setIdTeamNew] = useState(0);
    const [idDriver, setIdDriver] = useState(0);
    const [idDriverNew, setIdDriverNew] = useState(0);
    const [autorizationDispatch, setAutorizationDispatch] = useState(false);
    
    const [latitude, setLatitude]   = useState(0);
    const [longitude, setLongitude] = useState(0);

    const [textMessage, setTextMessage]                 = useState('');
    const [textMessageDate, setTextMessageDate]         = useState('');
    const [typeMessageDispatch, setTypeMessageDispatch] = useState('');

    const [typeMessage, setTypeMessage] = useState('');

    const [file, setFile]             = useState('');

    const [page, setPage]                 = useState(1);
    const [totalPage, setTotalPage]       = useState(0);
    const [totalPackage, setTotalPackage] = useState(0);

    const [RouteSearchList, setRouteSearchList] = useState('all');
    const [StateSearch, setStateSearch]         = useState('all');
    const [idCompany, setCompany]               = useState(0);

    const [isLoading, setIsLoading] = useState(false);

    const inputFileRef = React.useRef();

    const [viewButtonSave, setViewButtonSave] = useState('none');

    document.getElementById('bodyAdmin').style.backgroundColor = '#d1e7dd';

    useEffect(() => {

        if("geolocation" in navigator)
        {
            console.log("Available");

            navigator.geolocation.getCurrentPosition(function(position) {

                setLatitude(position.coords.latitude);
                setLongitude(position.coords.longitude);

                console.log("Latitude is:", position.coords.latitude);
                console.log("Longitude is :", position.coords.longitude);
            });
        }
        else
        {
            swal('Error', 'El navegador no soporta compartir su ubicación, por favor use otro navegador,', 'error');
        }

        listAllCompany();
        listAllRoute();

        document.getElementById('Reference_Number_1').focus();

    }, []);

    useEffect(() => {

    }, [Reference_Number_1])

    useEffect(() => {

        setPage(1);

        listAllPackageDispatch(1, StateSearch, RouteSearchList);

    }, [idCompany, idTeam, idDriver, dateStart,dateEnd]);

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

    const listAllPackageDispatch = (pageNumber, StateSearch, RouteSearchList) => {

        setIsLoading(true);

        fetch(url_general +'package-dispatch/list/'+ idCompany +'/'+ dateStart +'/'+ dateEnd +'/'+ idTeam +'/'+ idDriver +'/'+ StateSearch +'/'+ RouteSearchList +'/?page='+ pageNumber)
        .then(res => res.json())
        .then((response) => {

            setIsLoading(false);
            setListPackageDispatch(response.packageDispatchList.data);
            setTotalPackage(response.packageDispatchList.total);
            setTotalPage(response.packageDispatchList.per_page);
            setPage(response.packageDispatchList.current_page);
            setQuantityDispatch(response.quantityDispatch);
            setQuantityDispatchAll(response.quantityDispatchAll);
            setQuantityFailed(response.quantityFailed);
            setQuantityHighPriority(response.quantityHighPriority);
            setRoleUser(response.roleUser);
            setListState(response.listState);

            if(listState.length == 0)
            {
                listOptionState(response.listState);
            }

            if(response.roleUser == 'Administrador')
            {
                listAllTeam();
            }
            else
            {
                listAllDriverByTeam(idUserGeneral);
                setIdTeam(idUserGeneral);
            }

            if(response.quantityDispatchAll > 0 || response.quantityFailed > 0)
            {
                setAutorizationDispatch(false);
            }
            else
            {
                setAutorizationDispatch(true);
            }
        });
    }

    const exportAllPackageDispatch = ( StateSearch, RouteSearchList, type) => {
        
        let url = url_general +'package-dispatch/export/'+ idCompany +'/'+ dateStart +'/'+ dateEnd +'/'+ idTeam +'/'+ idDriver +'/'+ StateSearch +'/'+ RouteSearchList +'/'+type;

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

        exportAllPackageDispatch(StateSearch, RouteSearchList, type);
    }

    const handlerRedirectToDebrief = () => {

        location.href = url_general +'driver/defrief';
    }

    const handlerChangePage = (pageNumber) => {

        listAllPackageDispatch(pageNumber, StateSearch, RouteSearchList);
    }

    const listAllCompany = () => {

        setListCompany([]);

        fetch(url_general +'company/getAll')
        .then(res => res.json())
        .then((response) => {

            setListCompany([{id:0,name:"ALL"},...response.companyList]);
        });
    }

    const listAllRoute = (pageNumber) => {

        setListRoute([]);

        fetch(url_general +'routes/getAll')
        .then(res => res.json())
        .then((response) => {

            setListRoute(response.routeList);
            listOptionRoute(response.routeList);
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

    const [readOnlyInput, setReadOnlyInput]   = useState(false);
    const [disabledButton, setDisabledButton] = useState(false);

    const [textButtonSave, setTextButtonSave] = useState('Move Packages');

    const optionsRole = listRoute.map( (route, i) => {

        return (

            <option key={ i } value={ route.name } selected={ Route == route.name ? true : false }> {route.name}</option>
        );
    });

    const optionCompany = listCompany.map( (company, i) => {

        return <option value={company.id}>{company.name}</option>
    })

    const handlerOpenModalEditPackage = (PACKAGE_ID) => {

        fetch(url_general +'package-dispatch/get/'+ PACKAGE_ID)
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

        clearValidationEdit();

        let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        setDisabledButton(true);
        setTextButtonSave('Loading...');

        let url = 'package-dispatch/update'

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

                    listAllPackageDispatch(page, StateSearch, RouteSearchList);
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

    const clearValidationEdit = () => {

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
                                                                    <input type="text" value={ Dropoff_Address_Line_2 } className="form-control" onChange={ (e) => setDropoff_Address_Line_2(e.target.value) }/>
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

    const listAllTeam = () => {

        fetch(url_general +'team/listall')
        .then(res => res.json())
        .then((response) => {

            setListTeam(response.listTeam);
        });
    }

    const listAllDriverByTeam = (idTeam) => {

        if(idTeam)
        {
            setIdTeam(idTeam);
            setIdDriver(0);
            setListDriver([]);

            fetch(url_general +'driver/team/list/'+ idTeam)
            .then(res => res.json())
            .then((response) => {

                setListDriver(response.listDriver);
            });
        }
        else
        {
            setIdTeam(0);
            setListDriver([]);
        }
    }

    const listAllDriverByTeamAssign = (idTeam) => {

        setIdTeamNew(idTeam);
        setIdDriverNew(0);
        setListDriverAssign([]);

        fetch(url_general +'driver/team/list/'+ idTeam)
        .then(res => res.json())
        .then((response) => {

            setListDriverAssign(response.listDriver);
        });
    }

    const [sendDispatch, setSendDispatch] = useState(1);

    const handlerValidation = (e) => {

        e.preventDefault();

        console.log(sendDispatch);

        if(sendDispatch)
        {
            setIsLoading(true);
            setReadOnly(true);
            setSendDispatch(0);

            const formData = new FormData();

            formData.append('Reference_Number_1', Reference_Number_1);
            formData.append('idTeam', idTeam);
            formData.append('idDriver', idDriver);
            formData.append('RouteSearch', RouteSearch);
            formData.append('autorizationDispatch', autorizationDispatch);
            formData.append('latitude', latitude);
            formData.append('longitude', longitude);

            if(latitude == 0 || longitude == 0)
            {
                swal('Attention!', 'You must share the location of your device and reload the window.', 'warning');

                return 0;
            }

            let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            fetch(url_general +'package-dispatch/insert', {
                headers: { "X-CSRF-TOKEN": token },
                method: 'post',
                body: formData
            })
            .then(res => res.json()).
            then((response) => {

                    setIsLoading(false);

                    if(response.stateAction == 'notAutorization')
                    {
                        setTextMessage('This driver has packages pending return. You must mark the authorization to make the dispatch #'+ Reference_Number_1);
                        setTypeMessageDispatch('warning');
                        setNumberPackage('');
                    }
                    else if(response.stateAction == 'validatedReturnCompany')
                    {
                        setTextMessage("The package was registered before for return to the company #"+ Reference_Number_1);
                    }
                    else if(response.stateAction == 'packageInPreDispatch')
                    {
                        setTextMessage('The package is in  PRE DISPATCH #'+ Reference_Number_1);
                        setTypeMessageDispatch('warning');
                        setNumberPackage('');

                        document.getElementById('soundPitidoWarning').play();
                    }
                    else if(response.stateAction == 'packageTerminal')
                    {
                        setTextMessage('The package is in TERMINAL STATUS #'+ Reference_Number_1);
                        setTypeMessageDispatch('warning');
                        setNumberPackage('');

                        document.getElementById('soundPitidoWarning').play();
                    }
                    else if(response.stateAction == 'packageNMI')
                    {
                        setTextMessage('The package is in NMI STATUS #'+ Reference_Number_1);
                        setTypeMessageDispatch('warning');
                        setNumberPackage('');

                        document.getElementById('soundPitidoWarning').play();
                    }
                    else if(response.stateAction == 'validatedLost')
                    {
                        setTextMessage("THE PACKAGE WAS RECORDED BEFORE AS LOST #"+ Reference_Number_1);
                        setTypeMessageDispatch('warning');

                        document.getElementById('soundPitidoWarning').play();
                    }
                    else if(response.stateAction == 'validatedFilterPackage')
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

                        if(packageManifest)
                        {
                            Swal.fire({
                                icon: 'error',
                                title: 'PACKAGE BLOCKED #'+ Reference_Number_1,
                                text: ( packageManifest.blockeds.length > 0 ? packageManifest.blockeds[0].comment : '' ),
                                showConfirmButton: false,
                                timer: 2000,
                            })
                        }
                        //setTextMessage(" LABEL #"+ Reference_Number_1);

                        //setTextMessage(" LABEL #"+ Reference_Number_1);


                        setTypeMessage('primary');
                        setNumberPackage('');

                        document.getElementById('soundPitidoBlocked').play();
                    }
                    else if(response.stateAction == 'notInbound')
                    {
                        setTextMessage("NOT VALIDATED INBOUND #"+ Reference_Number_1);
                        setTypeMessageDispatch('warning');
                        setNumberPackage('');

                        document.getElementById('soundPitidoWarning').play();
                    }
                    else if(response.stateAction == 'notRoute')
                    {
                        setTextMessage("NOT VALIDATED ROUTES #"+ Reference_Number_1);
                        setTypeMessageDispatch('warning');
                        setNumberPackage('');

                        document.getElementById('soundPitidoWarning').play();
                    }
                    else if(response.stateAction == 'notDimensions')
                    {
                        setTextMessage("PACKAGE HAS NO RECORDED DIMENSIONS #"+ Reference_Number_1);
                        setTypeMessageDispatch('warning');
                        setNumberPackage('');

                        document.getElementById('soundPitidoWarning').play();
                    }
                    else if(response.stateAction == 'repairPackage')
                    {
                        setTextMessage("TASK NOT LOADED #"+ Reference_Number_1);
                        setTextMessageDate("VERIFY ADDRESS OR PHONE NUMBER");
                        setTypeMessageDispatch('error');
                        setNumberPackage('');

                        document.getElementById('soundPitidoError').play();
                    }
                    else if(response.stateAction == 'notSelectTeamDriver')
                    {
                        setTextMessage("TASK NOT LOADED #"+ Reference_Number_1);
                        setTextMessageDate("SELECT A TEAM AND A DRIVER");
                        setTypeMessageDispatch('error');
                        setNumberPackage('');

                        document.getElementById('soundPitidoError').play();
                    }
                    else if(response.stateAction == 'notInland')
                    {
                        setTextMessage("NOT INLAND o 67660 #"+ Reference_Number_1);
                        setTypeMessageDispatch('warning');
                        setNumberPackage('');

                        document.getElementById('soundPitidoWarning').play();
                    }
                    else if(response.stateAction == 'notExists')
                    {
                        setTextMessage("NO EXISTS #"+ Reference_Number_1);
                        setTypeMessageDispatch('error');
                        setNumberPackage('');

                        document.getElementById('soundPitidoError').play();
                    }
                    else if(response.stateAction == 'notValidatedRoute')
                    {
                        setTextMessage("El paquete N° "+ Reference_Number_1 +" no corresponde a su ruta asignada!");
                        setTypeMessageDispatch('error');
                        setNumberPackage('');

                        document.getElementById('Reference_Number_1').focus();
                        document.getElementById('soundPitidoError').play();
                    }
                    else if(response.stateAction == 'validated')
                    {
                        let packageDispatch = response.packageDispatch;

                        let team   = packageDispatch.driver.nameTeam ? packageDispatch.driver.nameTeam : packageDispatch.driver.name;
                        let driver = packageDispatch.driver.nameTeam ? packageDispatch.driver.name +' '+ packageDispatch.driver.nameOfOwner  : '';

                        let textDate =  packageDispatch.Date_Dispatch.substring(5, 7) +'-'+ packageDispatch.Date_Dispatch.substring(8, 10) +'-'+
                                        packageDispatch.Date_Dispatch.substring(0, 4) +'-'+ packageDispatch.Date_Dispatch.substring(11, 19) +' / '+
                                        team +' / '+ driver;

                        setTextMessage("VALIDATE:  #"+ Reference_Number_1 +' / '+ packageDispatch.Route);
                        setTextMessageDate(textDate);
                        setTypeMessageDispatch('warning');
                        setNumberPackage('');

                        document.getElementById('soundPitidoWarning').play();
                    }
                    else if(response.stateAction == 'returCompany')
                    {
                        setTextMessage("The package N°"+ Reference_Number_1 +" was returned to the company!");
                        setTypeMessageDispatch('warning');
                        setNumberPackage('');

                        document.getElementById('soundPitidoWarning').play();
                    }
                    else if(response.stateAction == 'packageExist')
                    {
                        setTextMessage("El paquete N° "+ Reference_Number_1 +" existe, pero no pasó la validación Inbound!");
                        setTypeMessageDispatch('warning');
                        setNumberPackage('');

                        document.getElementById('soundPitidoWarning').play();
                    }
                    else if(response.stateAction == 'delivery')
                    {
                        setTextMessage("PACKAGE WAS MARKED AS DELIVERED #"+ Reference_Number_1);
                        setTypeMessageDispatch('warning');
                        setNumberPackage('');

                        document.getElementById('soundPitidoWarning').play();
                    }
                    else if(response.stateAction == 'errorXcelerator')
                    {
                        setTextMessage(response.response.Message +" #"+ Reference_Number_1);
                        setTypeMessageDispatch('error');
                        setNumberPackage('');

                        document.getElementById('soundPitidoError').play();
                    }
                    else if(response.stateAction == 'assigned')
                    {
                        setTextMessage("PACKAGE ASSIGNED TO VIRTUAL OFFICE #"+ Reference_Number_1);
                        setTypeMessageDispatch('warning');
                        setNumberPackage('');

                        document.getElementById('soundPitidoWarning').play();
                    }
                    else if(response.stateAction)
                    {
                        setTextMessage("SUCCESSFULLY DISPATCHED #"+ Reference_Number_1);
                        setTextMessageDate('');
                        setTypeMessageDispatch('success');
                        setNumberPackage('');

                        listAllPackageDispatch(1, StateSearch, RouteSearchList);

                        document.getElementById('Reference_Number_1').focus();
                        document.getElementById('soundPitidoSuccess').play();
                    }
                    else
                    {
                        setTextMessage("El paquete N° "+ Reference_Number_1 +" no existe!");
                        setTypeMessageDispatch('error');
                        setNumberPackage('');

                        document.getElementById('Reference_Number_1').focus();
                        document.getElementById('soundPitidoError').play();
                    }

                    setReadOnly(false);
                    setSendDispatch(1);
                },
            );
        }
        /*if(autorizationDispatch == true)
        {
            
        }
        else
        {
            swal("You must mark the authorization to carry out the dispatch!", {

                icon: "warning",
            });
        }*/
    }

    const handlerImport = (e) => {

        e.preventDefault();

        if(idTeam != 0 || idDriver != 0)
        {
            const formData = new FormData();

            formData.append('idDriver', idDriver);
            formData.append('idTeam', idTeam);
            formData.append('file', file);

            let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            LoadingShow();

            fetch(url_general +'package-dispatch/import', {
                headers: { "X-CSRF-TOKEN": token },
                method: 'post',
                body: formData
            })
            .then(res => res.json())
            .then((response) => {

                    if(response.stateAction)
                    {
                        swal("Se importó el archivo!", {

                            icon: "success",
                        });

                        document.getElementById('fileImport').value = '';

                        listAllPackageDispatch(1, StateSearch, RouteSearchList);

                        setViewButtonSave('none');
                    }

                    LoadingHide();
                },
            );
        }
        else
        {
            swal('Atención!', 'Debe seleccionar mínimo un Team para importar', 'warning');
        }
    }

    const changeReference = (e) => {

        e.preventDefault();

        /*if(idDriverAsing == 0)
        {
            swal('Atención!', 'Debe seleccionar un Driver para asignar el paquete', 'warning');

            return 0;
        }*/

        let formData = new FormData();

        formData.append('Reference_Number_1', Reference_Number_1);
        formData.append('idTeam', idTeam);
        formData.append('idDriver', idDriverAsing);

        let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        let url = 'package-dispatch/change'

        fetch(url_general + url, {
            headers: { "X-CSRF-TOKEN": token },
            method: 'post',
            body: formData
        })
        .then(res => res.json()).
        then((response) => {

                if(response.stateAction == true)
                {
                    setTextMessage("RE-ASSIGN PACKAGE DISPATCHED #"+ Reference_Number_1);
                    setTypeMessageDispatch('success');
                    setNumberPackage('');

                    listAllPackageDispatch(1, StateSearch, RouteSearchList);

                    document.getElementById('Reference_Number_1').focus();
                    document.getElementById('soundPitidoSuccess').play();

                    setTextButtonSave('Guardar');
                    setDisabledButton(false);
                }

            },
        );

        /*swal({
            title: "Esta seguro?",
            text: "Se asignará el paquete al Driver!",
            icon: "warning",
            buttons: true,
            dangerMode: true,
        })
        .then((willDelete) => {

            if(willDelete)
            {

            }
        });*/
    }

    const listPackageDispatchTable = listPackageDispatch.map( (packageDispatch, i) => {

        let team   = (packageDispatch.team ? packageDispatch.team.name : '');
        let driver = (packageDispatch.driver ? packageDispatch.driver.name +' '+ packageDispatch.driver.nameOfOwner : '');

        return (

            <tr key={i}>
                <td style={ { width: '100px'} }>
                    { packageDispatch.created_at.substring(5, 7) }-{ packageDispatch.created_at.substring(8, 10) }-{ packageDispatch.created_at.substring(0, 4) }
                </td>
                <td>
                    { packageDispatch.created_at.substring(11, 19) }
                </td>
                <td><b>{ packageDispatch.company }</b></td>
                {
                    roleUser == 'Administrador'
                    ?
                        <>
                            <td><b>{ team }</b></td>
                            <td><b>{ driver }</b></td>
                        </>


                    :
                        ''
                }
                {
                    roleUser == 'Team'
                    ?
                        <td><b>{ driver }</b></td>
                    :
                        ''
                }
                <td><b>{ packageDispatch.Reference_Number_1 }</b></td>
                <td>{ packageDispatch.Dropoff_Contact_Name }</td>
                <td>{ packageDispatch.Dropoff_Contact_Phone_Number }</td>
                <td>{ packageDispatch.Dropoff_Address_Line_1 }</td>
                <td>{ packageDispatch.Dropoff_City }</td>
                <td>{ packageDispatch.Dropoff_Province }</td>
                <td>{ packageDispatch.Dropoff_Postal_Code }</td>
                <td>{ packageDispatch.Weight }</td>
                <td>{ packageDispatch.Route }</td>
                <td>{ packageDispatch.taskOnfleet }</td>
                <td style={ {display: 'none'} }>
                    { idUserGeneral == packageDispatch.idUserDispatch && roleUser == 'Team' ? <><button className="btn btn-success btn-sm" value={ packageDispatch.Reference_Number_1 } onClick={ (e) => changeReference(packageDispatch.Reference_Number_1) }>Asignar</button><br/><br/></> : '' }
                    <button className="btn btn-primary btn-sm" onClick={ () => handlerOpenModalEditPackage(packageDispatch.Reference_Number_1) }>
                        <i className="bx bx-edit-alt"></i>
                    </button>
                </td>
            </tr>
        );
    });

    const listTeamSelect = listTeam.map( (team, i) => {

        return (

            <option value={ team.id } className={ (team.useXcelerator == 1 ? 'text-warning' : '') }>{ team.name }</option>
        );
    });

    const listTeamNowSelect = listTeamNow.map( (team, i) => {

        return (

            <option value={ team.id } className={ (team.useXcelerator == 1 ? 'text-warning' : '') }>{ team.name }</option>
        );
    });

    const listTeamNewSelect = listTeamNew.map( (team, i) => {

        return (

            <option value={ team.id } className={ (team.useXcelerator == 1 ? 'text-warning' : '') }>{ team.name }</option>
        );
    });

    const listDriverSelect = listDriver.map( (driver, i) => {

        return (

            <option value={ driver.id }>{ driver.name +' '+ driver.nameOfOwner }</option>
        );
    });

    const listDriverSelectAssign = listDriverAssign.map( (driver, i) => {

        return (

            <option value={ driver.id }>{ driver.name +' '+ driver.nameOfOwner }</option>
        );
    });

    const handlerReturn = (idPackage) => {

        const formData = new FormData();

        formData.append('Reference_Number_1', idPackage);

        let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        setReadOnly(true);

        fetch(url_general +'package/return/dispatch', {
            headers: { "X-CSRF-TOKEN": token },
            method: 'post',
            body: formData
        })
        .then(res => res.json()).
        then((response) => {

                if(response.stateAction == 'notUser')
                {
                    setTextMessage("El paquete N° "+ idPackage +" fue validado por otro Driver!");
                    setTypeMessage('error');
                    setReturnNumberPackage('');

                    document.getElementById('return_Reference_Number_1').focus();
                    document.getElementById('soundPitidoError').play();
                }
                else if(response.stateAction == 'notDispatch')
                {
                    setTextMessage("El paquete N° "+ idPackage +" no fue validado como Dispatch!");
                    setTypeMessage('warning');
                    setNumberPackage('');
                }
                else if(response.stateAction)
                {
                    setTextMessage("Paquete N° "+ idPackage +" fue retornado!");
                    setTypeMessage('success');
                    setNumberPackage('');

                    document.getElementById('return_Reference_Number_1').focus();
                    document.getElementById('soundPitidoSuccess').play();

                    listAllPackageDispatch();
                }
                else
                {
                    setTextMessage("Hubo un problema, intente nuevamente realizar la misma acción.");
                    setTypeMessage('error');
                    setNumberPackage('');

                    document.getElementById('return_Reference_Number_1').focus();
                    document.getElementById('soundPitidoError').play();
                }

                setReadOnly(false);
            },
        );
    }

    const handlerDownloadOnFleet = () => {

        if(dayNight)
        {
            var checkboxes = document.getElementsByName('checkDispatch');

            let countCheck = 0;

            let valuesCheck = '';

            for(var i = 0; i < checkboxes.length ; i++)
            {
                if(checkboxes[i].checked)
                {
                    valuesCheck = (valuesCheck == '' ? checkboxes[i].value : valuesCheck +','+ checkboxes[i].value);

                    countCheck++;
                }
            }

            let type = 'all';

            if(countCheck)
            {
                type = 'check'
            }

            if(valuesCheck == '')
            {
                valuesCheck = 'all';
            }

            location.href = url_general +'package/download/onfleet/'+ idTeam +'/'+ idDriver +'/'+ type +'/'+ valuesCheck +'/'+ StateSearch +'/'+ dayNight;
        }
        else
        {
            swal('Attention!', 'Select a time', 'warning')
        }
    }

    const [RouteSearchRoadWarrior, setRouteSearchRoadWarrior] = useState('');

    const handlerDownloadRoadWarrior = () => {

        location.href = url_general +'package/download/roadwarrior/'+ idCompany +'/'+ idTeam +'/'+ idDriver +'/'+ StateSearch+'/'+ RouteSearchList +'/'+ dateStart +'/'+ dateEnd;
    }

    const clearForm = () => {

        setReturnNumberPackage('');
        setDescriptionReturn('');
    }

    const clearValidation = () => {

        document.getElementById('returnReference_Number_1').style.display = 'none';
        document.getElementById('returnReference_Number_1').innerHTML     = '';

        document.getElementById('descriptionReturn').style.display = 'none';
        document.getElementById('descriptionReturn').innerHTML     = '';
    }

    const onBtnClickFile = () => {

        setViewButtonSave('none');

        inputFileRef.current.click();
    }

    const hanldlerCheckAll = () => {

        if(checkAll == 0)
        {
            var checkboxes = document.getElementsByName('checkDispatch');

            for(var i = 0; i < checkboxes.length ; i++)
            {
                checkboxes[i].checked = 1;
            }

            setCheckAll(1);
        }
        else
        {
            var checkboxes = document.getElementsByName('checkDispatch');

            for(var i = 0; i < checkboxes.length ; i++)
            {
                checkboxes[i].checked = 0;
            }

            setCheckAll(0);
        }
    }

    const handlerRedirectReturns = () => {

        location.href = 'package/return';
    }


    const listAllRole = () => {

        fetch(url_general +'role/list')
        .then(res => res.json())
        .then((response) => {

            setListRole(response.roleList);
        });
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
        formData.append('idsRoutes', idsRoutes);
        formData.append('permissionDispatch', permissionDispatch);

        //clearValidationTeam();

        let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        LoadingShow();

        fetch(url_general +'team/insert', {
            headers: { "X-CSRF-TOKEN": token },
            method: 'post',
            body: formData
        })
        .then(res => res.json()).
        then((response) => {

                if(response.stateAction)
                {
                    swal("Team was registered!", {

                        icon: "success",
                    });

                    listAllTeam();
                    clearFormTeam();
                    listAllRoute();
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

    const listRoleSelect = listRole.map( (role, i) => {

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
                    <input class="form-check-input" type="checkbox" id={ 'idCheck'+ route.id } value={ route.id } onChange={ () => handleChange() }/>
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

            if(document.getElementById('idCheck'+ route.id).checked)
            {
                routesIds = (routesIds == '' ? route.id : route.id +','+ routesIds);
            }
        });

        setIdsRoutes(routesIds);
    };

    const clearValidationTeam = () => {

        document.getElementById('idRole').style.display = 'none';
        document.getElementById('idRole').innerHTML     = '';

        document.getElementById('name').style.display = 'none';
        document.getElementById('name').innerHTML     = '';

        document.getElementById('nameOfOwner').style.display = 'none';
        document.getElementById('nameOfOwner').innerHTML     = '';

        document.getElementById('address').style.display = 'none';
        document.getElementById('address').innerHTML     = '';

        document.getElementById('phone').style.display = 'none';
        document.getElementById('phone').innerHTML     = '';

        document.getElementById('email').style.display = 'none';
        document.getElementById('email').innerHTML     = '';
    }

    const clearFormTeam = () => {

        setId(0);
        setIdRole(0);
        setName('');
        setNameOfOwner('');
        setAddress('');
        setPhone('');
        setEmail('');
    }

    const handlerOpenOtherTeam = (id) => {

        listAllRole();
        clearFormTeam();

        setListTeamNow(listTeam);

        let myModal = new bootstrap.Modal(document.getElementById('modalOtherTeam'), {

            keyboard: true
        });

        myModal.show();
    }

    const handlerSaveUser = (e) => {

        e.preventDefault();

        const formData = new FormData();

        formData.append('idRole', idRole);
        formData.append('idTeam', (roleUser == 'Administrador' ? idTeam : idUserGeneral));
        formData.append('name', name);
        formData.append('nameOfOwner', nameOfOwner);
        formData.append('address', address);
        formData.append('phone', phone);
        formData.append('email', email);

        //clearValidation();

        let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        LoadingShow();

        fetch(url_general +'driver/insert', {
            headers: { "X-CSRF-TOKEN": token },
            method: 'post',
            body: formData
        })
        .then(res => res.json()).
        then((response) => {

                if(response.stateAction)
                {
                    swal("Driver was registered!", {

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

    const listRoleDriverSelect = listRole.map( (role, i) => {

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

    const handlerChangeTeamNow = (id) => {

        setListTeamNew([]);
        setIdTeamNow(id);
        setListDriverAssign([]);

        let auxListTeamNow = listTeamNow.filter( team => team.id != id);

        setListTeamNew(auxListTeamNow);
    }

    const [packagesMovedList, setPackagesMovedList]       = useState([]);
    const [packagesNotMovedList, setPackagesNotMovedList] = useState([]);

    const handlerChangeTeamOfPackages = (e) => {

        LoadingShowMap();

        e.preventDefault();

        const formData = new FormData();

        formData.append('idTeamNow', idTeamNow);
        formData.append('idTeamNew', idTeamNew);
        formData.append('idDriverNew', idDriverNew); 

        let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        fetch(url_general +'package-dispatch/update/change-team', {
            headers: { "X-CSRF-TOKEN": token },
            method: 'post',
            body: formData
        })
        .then(res => res.json())
        .then((response) => {

            if(response.statusCode == true)
            {
                setPackagesMovedList(response.packagesMovedList);
                setPackagesNotMovedList(response.packagesNotMovedList);

                swal('Correct!', 'The packages was assigned to the new TEAM', 'success');
            }
            else if(response.statusCode == false)
            {
                swal('Error!', 'An error has occurred, please try again', 'warning');
            }
            else if(response.statusCode == 'notExists')
            {
                swal('Attention!', 'The team has not packages in DISPATCH', 'warning');
            }
            else
            {
                swal('Error!', 'A problem occurred, please try again', 'error');
            }

            LoadingHideMap();
        });
    }

    const packagesMovedListTable = packagesMovedList.map((packageMoved, i) => {

        return (

            <tr key={ i }>
                <td>{{ packageMoved }}</td>
            </tr>
        );
    });

    const packagesNotMovedListTable = packagesMovedList.map((packageNotMoved, i) => {

        return (

            <tr key={ i }>
                <td>{{ packageNotMoved }}</td>
            </tr>
        );
    });

    const modalOtherTeam = <React.Fragment>
                                    <div className="modal fade" id="modalOtherTeam" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div className="modal-dialog modal-lg">
                                            <form onSubmit={ handlerChangeTeamOfPackages }>
                                                <div className="modal-content">
                                                    <div className="modal-header">
                                                        <h5 className="modal-title text-primary" id="exampleModalLabel">MOVE PACKAGES OF A TEAM O OTHER </h5>
                                                        <button type="button" className="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div className="modal-body">
                                                        <div className="row mb-3">
                                                            <div className="col-lg-4">
                                                                <div className="form-group mb-3">
                                                                    <label className="form">TEAM TO REMOVE PACKAGES</label>
                                                                    <select name="" id="" className="form-control" onChange={ (e) => handlerChangeTeamNow(e.target.value) } required>
                                                                        <option value="">All</option>
                                                                        { listTeamNowSelect }
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div className="col-lg-4">
                                                                <div className="form-group mb-3">
                                                                    <label className="form">TEAM TO ASSIGN PACKAGES</label>
                                                                    <select name="" id="" className="form-control" onChange={ (e) => listAllDriverByTeamAssign(e.target.value) } required>
                                                                        <option value="">All</option>
                                                                        { listTeamNewSelect }
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div className="col-lg-4">
                                                                <div className="form-group">
                                                                    <label className="form">TEAM TO ASSIGN PACKAGES</label>
                                                                    <select name="" id="" className="form-control" onChange={ (e) => setIdDriverNew(e.target.value) } required>
                                                                        <option value="">All</option>
                                                                        { listDriverSelectAssign }
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div className="row">
                                                            <div className="col-lg-12">
                                                                <label className="form">PACKAGES MOVED LIST</label>
                                                                <table>
                                                                    <thead>
                                                                        <tr>
                                                                            <th>PACKAGE ID</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        { packagesMovedListTable }
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                            <div className="col-lg-12">
                                                                <label className="form">PACKAGES NOT MOVED LIST</label>
                                                                <table>
                                                                    <thead>
                                                                        <tr>
                                                                            <th>PACKAGE ID</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        { packagesNotMovedListTable }
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

    const [optionsRoleSearch, setOptionsRoleSearch] = useState([]);

    const listOptionRoute = (listRoutes) => {

        setOptionsRoleSearch([]);

        listRoutes.map( (route, i) => {

            optionsRoleSearch.push({ value: route.name, label: route.name });

            setOptionsRoleSearch(optionsRoleSearch);
        });
    }

    const [RouteSearch, setRouteSearch] = useState('');

    const handlerChangeRoute = (routes) => {

        if(routes.length != 0)
        {
            let routesSearch = '';

            routes.map( (route) => {

                routesSearch = routesSearch == '' ? route.value : routesSearch +','+ route.value;
            });

            setRouteSearch(routesSearch);

            //listAllPackageInbound(page, dataView, routesSearch, StateSearch);
        }
        else
        {
            //setRouteSearch('all');

            //listAllPackageInbound(page, dataView, 'all', StateSearch);
        }
    };

    const handlerChangeRouteList = (routes) => {

        if(routes.length != 0)
        {
            let routesSearch = '';

            routes.map( (route) => {

                routesSearch = routesSearch == '' ? route.value : routesSearch +','+ route.value;
            });

            setRouteSearchList(routesSearch);

            listAllPackageDispatch(1, StateSearch, routesSearch);
        }
        else
        {
            setRouteSearchList('all');

            listAllPackageDispatch(1, StateSearch, 'all');
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

    const handlerChangeState = (states) => {

        setPage(1);

        if(states.length != 0)
        {
            let statesSearch = '';

            states.map( (state) => {

                statesSearch = statesSearch == '' ? state.value : statesSearch +','+ state.value;
            });

            setStateSearch(statesSearch);

            listAllPackageDispatch(1, statesSearch, RouteSearchList);
        }
        else
        {
            setStateSearch('all');

            listAllPackageDispatch(1, 'all', RouteSearchList);
        }
    }

    const handlerRedirectFailed = () => {

        location.href = url_general +'package-failed';
    }

    const handlerRedirectHighPriority = () => {

        location.href = url_general +'package-high-priority';
    }

    const handlerAutorization = () => {

        setAutorizationDispatch(!autorizationDispatch);
    }

    return (

        <section className="section">
            { modalOtherTeam }
            { modalPackageEdit }
            <div className="row">
                <div className="col-lg-12">
                    <div className="card">
                        <div className="card-body">
                            <h5 className="card-title">
                                <div className="row form-group">
                                    <div className="col-lg-12 form-group">
                                        <div className="row form-group">
                                            <div className="col-lg-1">
                                                <div className="form-group">
                                                    <button className="btn btn-danger btn-sm form-control" onClick={ () => handlerDownloadRoadWarrior() }>ROADW</button>
                                                </div> 
                                            </div>
                                            <div className="col-lg-2">
                                                <div className="form-group">
                                                    <button className="btn btn-success btn-sm form-control" onClick={  () => handlerExport('download') }>
                                                        <i className="ri-file-excel-fill"></i> EXPORT
                                                    </button>
                                                </div>
                                            </div>
                                            <div className="col-3">
                                                <div className="form-group">
                                                    <button className="btn btn-warning btn-sm form-control text-white" onClick={  () => handlerExport('send') }>
                                                        <i className="ri-file-excel-fill"></i> EXPORT TO THE MAIL
                                                    </button>
                                                </div>
                                            </div>

                                            {
                                                roleUser == 'Administrador'
                                                ?
                                                    <div className="col-lg-2">
                                                        <form onSubmit={ handlerImport }>
                                                            <div className="form-group">
                                                                <button type="button" className="btn btn-primary btn-sm form-control" onClick={ () => onBtnClickFile() }>
                                                                    IMPORT CSV
                                                                </button>
                                                                <input type="file" id="fileImport" className="form-control" ref={ inputFileRef } style={ {display: 'none'} } onChange={ (e) => setFile(e.target.files[0]) } accept=".csv" required/>
                                                            </div>
                                                            <div className="form-group" style={ {display: viewButtonSave} }>
                                                                <button className="btn btn-primary btn-sm form-control" onClick={ () => handlerImport() }>
                                                                    <i className="bx  bxs-save"></i> Save
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                :
                                                    ''
                                            }

                                            <div className="col-2">
                                                <div className="form-group">
                                                    <button className="btn btn-info btn-sm form-control text-white" onClick={  () => handlerRedirectToDebrief() }>
                                                        DEBRIEF
                                                    </button>
                                                </div>
                                            </div>
                                            <div className="col-2">
                                                <div className="form-group">
                                                    <button className="btn btn-secondary btn-sm form-control text-white" onClick={  () => handlerOpenOtherTeam() }>
                                                        OTHER TEAM
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div className="row">
                                    <div className="col-lg-10">
                                        <form onSubmit={roleUser == 'Team' ? changeReference : handlerValidation} autoComplete="off">
                                            <div className="row form-group">
                                                <div className={ roleUser == 'Administrador' ? 'col-lg-6' : roleUser == 'Team' ? 'col-lg-10' : 'col-lg-12' }>
                                                    <div className="form-group">
                                                        <label htmlFor="">PACKAGE ID</label>
                                                        <input id="Reference_Number_1" type="text" className="form-control" value={ Reference_Number_1 } onChange={ (e) => setNumberPackage(e.target.value) } maxLength="24" required readOnly={ readOnly }/>
                                                    </div>
                                                </div>
                                                {
                                                    roleUser == 'Administrador'
                                                    ?
                                                        <>
                                                            <div className="col-lg-3">
                                                                <div className="form-group">
                                                                    <label htmlFor="">TEAM</label>
                                                                    <select name="" id="" className="form-control" onChange={ (e) => listAllDriverByTeam(e.target.value) } required>
                                                                        <option value="">All</option>
                                                                        { listTeamSelect }
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div className="col-lg-3">
                                                                <div className="form-group">
                                                                    <label htmlFor="">DRIVER</label>
                                                                    <select name="" id="" className="form-control" onChange={ (e) => setIdDriver(e.target.value) } required>
                                                                        <option value="0">All</option>
                                                                        { listDriverSelect }
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </>
                                                    :
                                                        ''
                                                }

                                                {
                                                    roleUser == 'Team'
                                                    ?
                                                        <>
                                                            <div className="col-lg-2">
                                                                <div className="form-group">
                                                                    <label htmlFor="">DRIVER</label>
                                                                    <select name="" id="" className="form-control" onChange={ (e) => setIdDriverAsing(e.target.value) } required>
                                                                       <option value="" style={ {display: 'none'} }>Seleccione Driver</option>
                                                                        { listDriverSelect }
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </>
                                                    :
                                                        ''
                                                }
                                            </div>
                                            <div className="row">
                                                <div className="col-lg-12 text-center">
                                                    {
                                                        typeMessageDispatch == 'success'
                                                        ?
                                                            <h2 className="text-success">{ textMessage }</h2>
                                                        :
                                                            ''
                                                    }

                                                    {
                                                        typeMessageDispatch == 'error'
                                                        ?
                                                            <h2 className="text-danger">{ textMessage }</h2>
                                                        :
                                                            ''
                                                    }

                                                    {
                                                        typeMessageDispatch == 'warning'
                                                        ?
                                                            <h2 className="text-warning">{ textMessage }</h2>
                                                        :
                                                            ''
                                                    }

                                                    {
                                                        textMessageDate != ''
                                                        ?
                                                            <h2 className="text-warning">{ textMessageDate }</h2>
                                                        :
                                                            ''
                                                    }
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                    <div className="col-lg-2 form-group">
                                        <div className="row">
                                            <div className="col-lg-12">
                                                <label htmlFor="">ROUTES</label>
                                                <Select isMulti onChange={ (e) => handlerChangeRoute(e) } options={ optionsRoleSearch } />
                                            </div>
                                        </div>
                                    </div>
                                    <div className="col-lg-12 form-group" style={ {display: (quantityDispatchAll > 0 || quantityFailed > 0 ? 'block' : 'none')} }>
                                        <div className="row">
                                            <div className="col-sm-12" style={ {display: 'none'} }>
                                                <div className="form-check">
                                                    <input className="form-check-input" type="checkbox" id="gridCheck1" checked={ autorizationDispatch } onChange={ () => handlerAutorization() }/>
                                                    <label className="form-check-label text-danger" for="gridCheck1" >
                                                        DISPATCH VERIFICATION
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div className="col-lg-12 form-group">
                                        <audio id="soundPitidoSuccess" src="./sound/pitido-success.mp3" preload="auto"></audio>
                                        <audio id="soundPitidoError" src="./sound/pitido-error.mp3" preload="auto"></audio>
                                        <audio id="soundPitidoWarning" src="./sound/pitido-warning.mp3" preload="auto"></audio>
                                        <audio id="soundPitidoBlocked" src="./sound/pitido-blocked.mp3" preload="auto"></audio>
                                    </div>
                                </div>


                                <hr/><br/>

                                <div className="row">
                                    <div className="col-lg-3 mb-2" style={ {paddingLeft: (isLoading ? '5%' : '')} }>
                                        {
                                            (
                                                isLoading
                                                ? 
                                                    <ReactLoading type="bubbles" color="#A8A8A8" height={20} width={50} />
                                                :
                                                    <b className="alert alert-success" style={ {borderRadius: '10px', padding: '10px'} }>DISPATCH: { quantityDispatch }</b>
                                            )
                                        }
                                    </div>
                                    <div className="col-lg-3 mb-2">
                                        <div className="form-group">
                                            <b className="alert alert-warning" style={ {borderRadius: '10px', padding: '10px'} }> UNDELIVERED: { quantityDispatchAll }</b>
                                        </div><br/>
                                    </div>
                                    <div className="col-lg-3 mb-2">
                                        <div className="form-group">
                                            <b className="alert alert-danger pointer" onClick={ () => handlerRedirectFailed()  } style={ {borderRadius: '10px', padding: '10px'} }> FAILED TASKS: { quantityFailed }</b>
                                        </div>
                                    </div>
                                    <div className="col-lg-3 mb-2">
                                        <div className="form-group">
                                            <b className="alert alert-danger pointer" onClick={ () => handlerRedirectHighPriority()  } style={ {borderRadius: '10px', padding: '10px'} }> HIGH PRIORITY: { quantityHighPriority }</b>
                                        </div>
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
                                                    States :
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
                                                    Routes :
                                                </div>
                                            </div>
                                            <div className="col-lg-12">
                                                <Select isMulti onChange={ (e) => handlerChangeRouteList(e) } options={ optionsRoleSearch } />
                                            </div>
                                        </div>
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
                                                {
                                                    roleUser == 'Administrador'
                                                    ?
                                                        <th><b>TEAM</b></th>
                                                    :
                                                        ''
                                                }
                                                {
                                                    roleUser == 'Administrador'
                                                    ?
                                                        <th><b>DRIVER</b></th>
                                                    :

                                                        roleUser == 'Team' ? <th><b>DRIVER</b></th> : ''
                                                }
                                                <th>PACKAGE ID</th>
                                                <th>CLIENT</th>
                                                <th>CONTACT</th>
                                                <th>ADDRESS</th>
                                                <th>CITY</th>
                                                <th>STATE</th>
                                                <th>ZIP CODE</th>
                                                <th>WEIGHT</th>
                                                <th>ROUTE</th>
                                                <th>TASK ONFLEET</th>
                                                <th style={ {display: 'none'} }>ACTION</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            { listPackageDispatchTable }
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

export default PackageDispatch;

// DOM element
if (document.getElementById('packageDispatch')) {
    ReactDOM.render(<PackageDispatch />, document.getElementById('packageDispatch'));
}
