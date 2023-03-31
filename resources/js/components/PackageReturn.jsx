import React, { useState, useEffect } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'
import Select from 'react-select'
import moment from 'moment/moment'
import ReactLoading from 'react-loading';

function PackageReturn() {

    const [listPackageReturn, setListPackageReturn] = useState([]);
    const [listComment, setListComment]             = useState([]);
    const [listCompany , setListCompany]            = useState([]);
    const [roleUser, setRoleUser]                   = useState([]);
    const [listRoute, setListRoute]                 = useState([]);
    const [listState , setListState]                = useState([]);
    const [dateStart, setDateStart] = useState(auxDateInit);
    const [dateEnd, setDateEnd]   = useState(auxDateInit);
    const [idTeam, setIdTeam] = useState(0);
    const [idDriver, setIdDriver] = useState(0);

    const [listTeam, setListTeam]                       = useState([]);
    const [listDriver, setListDriver]                   = useState([]);

    const [readOnly, setReadOnly] = useState(false);

    const [Comment, setComment] = useState('');

    const [quantityReturn, setQuantityReturn] = useState(0);

    const [returnReference_Number_1, setReturnNumberPackage] = useState('');
    const [descriptionReturn, setDescriptionReturn] = useState('');

    const [Reference_Number_1, setNumberPackage] = useState('');
    const [CategoryReturn, setCategoryReturn]    = useState('');
    const [latitude, setLatitude]                = useState(0);
    const [longitude, setLongitude]              = useState(0);

    const [showReturnPackage, setShowReturnPackage] = useState('none');
    const [iconReturnPackage, setIconReturnPackage] = useState('bi bi-eye-fill');

    const [textMessage, setTextMessage] = useState('');
    const [typeMessage, setTypeMessage] = useState('');

    const [page, setPage]                 = useState(1);
    const [totalPage, setTotalPage]       = useState(0);
    const [totalPackage, setTotalPackage] = useState(0);

    const [RouteSearch, setRouteSearch] = useState('all');
    const [StateSearch, setStateSearch] = useState('all');
    const [idCompany, setCompany]       = useState(0);
    const [isLoading, setIsLoading]     = useState(false);

    const [textButtonSave, setTextButtonSave] = useState('Guardar');

    document.getElementById('bodyAdmin').style.backgroundColor = '#f8d7da';

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

        listAllRoute();
        listAllCompany();

    }, []);

    useEffect(() => {

        listAllPackageReturn(page, RouteSearch, StateSearch);

    }, [idCompany, idTeam, idDriver,dateStart,dateEnd]);

    const optionsComment = listComment.map( (comment, i) => {

        return (
            (
                <option key={ i } value={ comment.description }> { comment.description }</option>
            )

        );
    });

    const listAllPackageReturn = (pageNumber, route, state) => {

        setIsLoading(true);

        fetch(url_general +'package/list/return/'+ idCompany +'/'+ dateStart +'/'+ dateEnd +'/'+ idTeam +'/'+ idDriver +'/'+ route +'/'+ state +'?page='+ pageNumber)
        .then(res => res.json())
        .then((response) => {

            setIsLoading(false);
            setListPackageReturn(response.packageReturnList.data);
            setQuantityReturn(response.quantityReturn);
            setTotalPackage(response.packageReturnList.total);
            setTotalPage(response.packageReturnList.per_page);
            setPage(response.packageReturnList.current_page);
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
        });
    }

    const listAllComment = (category) => {

        fetch(url_general +'comments/get-all-by-category/'+ category)
        .then(res => res.json())
        .then((response) => { 

            setListComment(response.commentList);
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

    const listAllRoute = () => {

        setListRoute([]);

        fetch(url_general +'routes/getAll')
        .then(res => res.json())
        .then((response) => {

            setListRoute(response.routeList);
            listOptionRoute(response.routeList);
        });
    }

    const handlerChangeRoute = (routes) => {

        if(routes.length != 0)
        {
            let routesSearch = '';

            routes.map( (route) => {

                routesSearch = routesSearch == '' ? route.value : routesSearch +','+ route.value;
            });

            setRouteSearch(routesSearch);

            listAllPackageReturn(page, routesSearch, StateSearch);
        }
        else
        {
            setRouteSearch('all');

            listAllPackageReturn(page, 'all', StateSearch);
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
    const handlerChangeState = (states) => {

        if(states.length != 0)
        {
            let statesSearch = '';

            states.map( (state) => {

                statesSearch = statesSearch == '' ? state.value : statesSearch +','+ state.value;
            });

            setStateSearch(statesSearch);

            listAllPackageReturn(page, RouteSearch, statesSearch);
        }
        else
        {
            setStateSearch('all');

            listAllPackageReturn(page, RouteSearch, 'all');
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

    const listTeamSelect = listTeam.map( (team, i) => {

        return (

            <option value={ team.id }>{ team.name }</option>
        );
    });

    const listDriverSelect = listDriver.map( (driver, i) => {

        return (

            <option value={ driver.id }>{ driver.name +' '+ driver.nameOfOwner }</option>
        );
    });

    const handlerChangePage = (pageNumber) => {

        listAllPackageReturn(pageNumber, RouteSearch, StateSearch);
    }

    const handlerOpenModal = (id) => {

        setTypeMessage('');
        clearValidation();
        clearForm();

        let myModal = new bootstrap.Modal(document.getElementById('modalReturnDispatch'), {

            keyboard: true
        });

        myModal.show();
    }

    const listPackageReturnTable = listPackageReturn.map( (packageReturn, i) => {

        let imgs      = '';
        let urlImage  = '';

        if(packageReturn.photoUrl)
        {
            let idsImages = packageReturn.photoUrl.split(',');

            imgs          = '';
            urlImage      = '';

            if(packageReturn.statusOnfleet == 3)
            {
                if(idsImages.length == 1)
                {
                    imgs = <img src={ 'https://d15p8tr8p0vffz.cloudfront.net/'+ idsImages[0] +'/800x.png' } width="100"/>;

                    urlImage = 'https://d15p8tr8p0vffz.cloudfront.net/'+ idsImages[0] +'/800x.png';
                }
                else if(idsImages.length >= 2)
                {
                    imgs =  <>
                                <img src={ 'https://d15p8tr8p0vffz.cloudfront.net/'+ idsImages[0] +'/800x.png' } width="50" style={ {border: '2px solid red'} }/>
                                <img src={ 'https://d15p8tr8p0vffz.cloudfront.net/'+ idsImages[1] +'/800x.png' } width="50" style={ {border: '2px solid red'} }/>
                            </>

                    urlImage = 'https://d15p8tr8p0vffz.cloudfront.net/'+ idsImages[0] +'/800x.png' + 'https://d15p8tr8p0vffz.cloudfront.net/'+ idsImages[1] +'/800x.png'
                }
            }
        }

        let team   = (packageReturn.team ? packageReturn.team.name : '');
        let driver = (packageReturn.driver ? packageReturn.driver.name +' '+ packageReturn.driver.nameOfOwner : '');

        return (

            <tr key={i} className={ packageReturn.statusOnfleet == 3 || packageReturn.statusOnfleet == 1 ? 'alert-warning' : 'alert-danger' }>

                <td style={ { width: '100px'} }>
                    { packageReturn.Date_Return ? packageReturn.Date_Return.substring(5, 7) +'-'+ packageReturn.Date_Return.substring(8, 10) +'-'+ packageReturn.Date_Return.substring(0, 4) : '' }
                </td>
                <td>
                    { packageReturn.Date_Return ? packageReturn.Date_Return.substring(11, 19):'' }
                </td>
                <td><b>{ packageReturn.company }</b></td>
                <td><b>{ team }</b></td>
                <td><b>{ driver }</b></td>
                <td><b>{ packageReturn.Reference_Number_1 }</b></td>
                <td>{ packageReturn.Description_Return }</td>
                <td>{ packageReturn.Description_Onfleet }</td>
                <td>{ packageReturn.Dropoff_Contact_Name }</td>
                <td>{ packageReturn.Dropoff_Contact_Phone_Number }</td>
                <td>{ packageReturn.Dropoff_Address_Line_1 }</td>
                <td>{ packageReturn.Dropoff_City }</td>
                <td>{ packageReturn.Dropoff_Province }</td>
                <td>{ packageReturn.Dropoff_Postal_Code }</td>
                <td>{ packageReturn.Route }</td>
                <td>{ packageReturn.taskOnfleet }</td>
                <td>{ packageReturn.statusOnfleet }</td>
                <td onClick={ () => viewImages(urlImage)} style={ {cursor: 'pointer'} }>
                    { imgs }
                </td>
                <td>
                    <button className="btn btn-primary btn-sm" onClick={ () => handlerOpenModal(packageReturn.Reference_Number_1) }>
                        <i className="bx bx-edit-alt"></i>
                    </button>
                </td>
            </tr>
        );
    });

    const [listViewImages, setListViewImages] = useState([]);

    const viewImages = (urlImage) => {

        setListViewImages(urlImage.split('https'));

        let myModal = new bootstrap.Modal(document.getElementById('modalViewImages'), {

            keyboard: true
        });

        myModal.show();
    }

    const listViewImagesModal = listViewImages.map( (image, i) => {

        if(i > 0)
        {
            return (

                <img src={ 'https'+ image } className="img-fluid mt-2"/>
            );
        }
    });

    const modalViewImages = <React.Fragment>
                                    <div className="modal fade" id="modalViewImages" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div className="modal-dialog">
                                            <div className="modal-content">
                                                <div className="modal-header">
                                                    <h5 className="modal-title text-primary" id="exampleModalLabel">View Images</h5>
                                                    <button type="button" className="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div className="modal-body">
                                                    { listViewImagesModal }
                                                </div>
                                                <div className="modal-footer">
                                                    <button type="button" className="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </React.Fragment>;

    const handlerSaveReturn = (e) => {

        e.preventDefault();

        const formData = new FormData();

        formData.append('Reference_Number_1', returnReference_Number_1);
        formData.append('CategoryReturn', CategoryReturn);
        formData.append('Description_Return', descriptionReturn);
        formData.append('latitude', latitude);
        formData.append('longitude', longitude);

        if(latitude == 0 || longitude == 0)
        {
            swal('Atención', 'Debe compartir la ubicación de su dispositivo y recargue la ventana.', 'warning');

            return 0;
        }
            
        clearValidation();

        let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        setReadOnly(true);
        setIsLoading(true);

        fetch(url_general +'package/return/dispatch', {
            headers: { "X-CSRF-TOKEN": token },
            method: 'post',
            body: formData
        })
        .then(res => res.json()).
        then((response) => {

                clearForm();
                setIsLoading(false);

                if(response.stateAction == 'validatedFilterPackage')
                {
                    let packageBlocked  = response.packageBlocked;

                    Swal.fire({
                        icon: 'error',
                        title: 'PACKAGE BLOCKED #'+ returnReference_Number_1,
                        text: packageBlocked.comment,
                        showConfirmButton: false,
                        timer: 2000,
                    });

                    setTypeMessage('primary');
                    setNumberPackage('');

                    document.getElementById('soundPitidoBlocked').play();
                }
                else if(response.stateAction == 'validatedReturnCompany')
                {
                    setTextMessage("The package was registered before for return to the company #"+ returnReference_Number_1);
                }
                else if(response.stateAction == 'packageInPreDispatch')
                {
                    setTextMessage('The package is in  PRE DISPATCH #'+ returnReference_Number_1);
                    setTypeMessage('warning');
                    setNumberPackage('');

                    document.getElementById('soundPitidoWarning').play();
                }
                else if(response.stateAction == 'validatedLost')
                {
                    setTextMessage("THE PACKAGE WAS RECORDED BEFORE AS LOST #"+ Reference_Number_1);
                    setTypeMessage('warning');

                    document.getElementById('soundPitidoWarning').play();
                }
                else if(response.stateAction == true)
                {
                    setTextMessage("Package N° "+ returnReference_Number_1 +" fue retornado!");
                    setTypeMessage('success');
                    setNumberPackage('');

                    document.getElementById('return_Reference_Number_1').focus();
                    document.getElementById('soundPitidoSuccess').play();

                    listAllPackageReturn(page, RouteSearch, StateSearch);
                }
                else if(response.stateAction == 'taskWasNotDelete')
                {
                    setTextMessage("The task was not deleted in Onfleet #"+ returnReference_Number_1);
                    setTypeMessage('error');
                    setReturnNumberPackage('');

                    document.getElementById('return_Reference_Number_1').focus();
                    document.getElementById('soundPitidoError').play();
                }
                else if(response.stateAction == 'notUser')
                {
                    setTextMessage("The package N° "+ returnReference_Number_1 +" fue validado por otro Driver!");
                    setTypeMessage('error');
                    setReturnNumberPackage('');

                    document.getElementById('return_Reference_Number_1').focus();
                    document.getElementById('soundPitidoError').play();
                }
                else if(response.stateAction == 'notDispatch')
                {
                    setTextMessage("The package #"+ returnReference_Number_1 +" was not validated as Dispatch!");
                    setTypeMessage('warning');
                    setNumberPackage('');

                    document.getElementById('return_Reference_Number_1').focus();
                    document.getElementById('soundPitidoWarning').play();
                }
                else if(response.stateAction)
                {
                    setTextMessage("Package N° "+ returnReference_Number_1 +" fue retornado!");
                    setTypeMessage('success');
                    setNumberPackage('');

                    document.getElementById('return_Reference_Number_1').focus();
                    document.getElementById('soundPitidoSuccess').play();

                    listAllPackageReturn(page, RouteSearch, StateSearch);
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
    const exportAllPackageReturn = (  route, state) => {

        location.href = url_general +'package/list/return/export/'+ idCompany +'/'+ dateStart +'/'+ dateEnd +'/'+ idTeam +'/'+ idDriver +'/'+ route +'/'+ state
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

        exportAllPackageReturn(RouteSearch, StateSearch);
    }
    const clearForm = () => {

        setReturnNumberPackage('');
    }

    const clearValidation = () => {

        document.getElementById('returnReference_Number_1').style.display = 'none';
        document.getElementById('returnReference_Number_1').innerHTML     = '';

        document.getElementById('descriptionReturn').style.display = 'none';
        document.getElementById('descriptionReturn').innerHTML     = '';
    }

    const modalReturnDispatch = <React.Fragment>
                                    <div className="modal fade" id="modalReturnDispatch" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div className="modal-dialog">

                                        </div>
                                    </div>
                                </React.Fragment>;

    const optionCompany = listCompany.map( (company, i) => {

        return <option value={company.id}>{company.name}</option>
    })

    const hanlderGetComment = (category) => {

        setCategoryReturn(category);
        listAllComment(category);
    }

    return (

        <section className="section">
            { modalViewImages }
            { modalReturnDispatch }
            <div className="row">
                <div className="col-lg-12">
                    <div className="card">
                        <div className="card-body">
                            <h5 className="card-title">
                                <div className="row form-group">
                                    <div className="col-lg-2 mb-2">
                                        <div className="form-group">
                                            <button className="btn btn-success btn-sm form-control" onClick={  () => handlerExport() }>
                                                <i className="ri-file-excel-fill"></i> EXPORT
                                            </button>
                                        </div>
                                    </div>
                                    <div className="col-lg-12 mb-2">
                                        <form onSubmit={ handlerSaveReturn } autoComplete="off">
                                            <div className="row">
                                                <div className="col-lg-3">
                                                    <div className="form-group">
                                                        <label>PACKAGE ID</label>
                                                        <div id="returnReference_Number_1" className="text-danger" style={ {display: 'none'} }></div>
                                                        <input id="return_Reference_Number_1" type="text" className="form-control" value={ returnReference_Number_1 } onChange={ (e) => setReturnNumberPackage(e.target.value) } maxLength="24" required readOnly={ readOnly }/>
                                                    </div>
                                                </div>

                                                <div className="col-lg-2">
                                                    <div className="form-group">
                                                        <label>RETURN CATEGORY</label>
                                                        <div id="returnCategory" className="text-danger" style={ {display: 'none'} }></div>
                                                        <select value={ CategoryReturn } className="form-control" onChange={ (e) => hanlderGetComment(e.target.value) } required>
                                                            <option value="" >Select item</option>
                                                            <option value="Retry" >Retry</option>
                                                            <option value="Terminal" >Terminal</option>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div className="col-lg-3">
                                                    <div className="form-group">
                                                        <label>RETURN COMMENT</label>
                                                        <div id="descriptionReturn" className="text-danger" style={ {display: 'none'} }></div>
                                                        <select name="" id="" className="form-control" onChange={ (e) => setDescriptionReturn(e.target.value) } required>
                                                            <option value="">Selection comment</option>
                                                            { optionsComment }
                                                        </select>
                                                    </div>
                                                    <br/>
                                                </div>
                                                {
                                                    roleUser == 'Administrador'
                                                    ?
                                                        <>
                                                            <div className="col-lg-2">
                                                                <div className="form-group">
                                                                    <label htmlFor="">TEAM</label>
                                                                    <select name="" id="" className="form-control" onChange={ (e) => listAllDriverByTeam(e.target.value) }>
                                                                        <option value="">All</option>
                                                                        { listTeamSelect }
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div className="col-lg-2">
                                                                <div className="form-group">
                                                                    <label htmlFor="">DRIVER</label>
                                                                    <select name="" id="" className="form-control" onChange={ (e) => setIdDriver(e.target.value) }>
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
                                                                    <select name="" id="" className="form-control" onChange={ (e) => setIdDriverAsing(e.target.value) }>
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
                                        </form>
                                        <audio id="soundPitidoSuccess" src="../sound/pitido-success.mp3" preload="auto"></audio>
                                        <audio id="soundPitidoError" src="../sound/pitido-error.mp3" preload="auto"></audio>
                                        <audio id="soundPitidoWarning" src="../sound/pitido-warning.mp3" preload="auto"></audio>
                                        <audio id="soundPitidoBlocked" src="../sound/pitido-blocked.mp3" preload="auto"></audio>
                                    </div>
                                    <div className="col-lg-12 mb-3 text-center">
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
                                            typeMessage == 'warning'
                                            ?
                                                <h2 className="text-warning">{ textMessage }</h2>
                                            :
                                                ''
                                        }
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
                                                    <b className="alert-success" style={ {borderRadius: '10px', padding: '10px'} }>Returns: { quantityReturn }</b>
                                            )
                                        }
                                    </div>
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
                                                <th>DESCRIPTION RETURN</th>
                                                <th>DESCRIPTION ONFLEET</th>
                                                <th>CLIENT</th>
                                                <th>CONTACT</th>
                                                <th>ADDRESS</th>
                                                <th>CITY</th>
                                                <th>STATE</th>
                                                <th>ZIP CODE</th>
                                                <th>ROUTE</th>
                                                <th>TASK ONFLEET</th>
                                                <th>STATUS ONFLEET</th>
                                                <th>IMG ONFLEET</th>
                                                <th>ACTION</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            { listPackageReturnTable }
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

export default PackageReturn;

// DOM element
if (document.getElementById('packageReturn')) {
    ReactDOM.render(<PackageReturn />, document.getElementById('packageReturn'));
}
