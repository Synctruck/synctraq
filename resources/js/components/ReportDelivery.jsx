import React, { useState, useEffect } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'
import Select from 'react-select'
import moment from 'moment'
import ReactLoading from 'react-loading';

function ReportDelivery() {

    const [listReport, setListReport]         = useState([]);
    const [listDeliveries, setListDeliveries] = useState([]);
    const [listTeam, setListTeam]             = useState([]);
    const [listDriver, setListDriver]         = useState([]);
    const [roleUser, setRoleUser]             = useState([]);
    const [listCompany , setListCompany]      = useState([]);

    const [quantityDispatch, setQuantityDispatch] = useState(0);

    const [Reference_Number_1, setReference_Number_1] = useState('');
    const [idTeamDelivery, setIdTeamDelivery]         = useState(0);
    const [Photo1, setPhoto1]                         = useState('');
    const [Photo2, setPhoto2]                         = useState('');
    const [DateDelivery, setDateDelivery]             = useState('');
    const [HourDelivery, setHourDelivery]             = useState(false);
    const [filePhoto1, setFilePhoto1]                 = useState('');
    const [filePhoto2, setFilePhoto2]                 = useState('');
    const [arrivalLonLat, setArrivalLonLat]           = useState('');
    const [disabledButton, setDisabledButton]         = useState(false);

    const [listRoute, setListRoute]  = useState([]);
    const [listState , setListState] = useState([]);

    const [dateInit, setDateInit] = useState(auxDateInit);
    const [dateEnd, setDateEnd]   = useState(auxDateInit);
    const [idTeam, setIdTeam]     = useState(id_team);
    const [idDriver, setIdDriver] = useState(id_driver);
    const [idCompany, setCompany] = useState(0);

    const [RouteSearch, setRouteSearch] = useState('all');
    const [StateSearch, setStateSearch] = useState('all');

    const [page, setPage]                 = useState(1);
    const [totalPage, setTotalPage]       = useState(0);
    const [totalPackage, setTotalPackage] = useState(0);
    const [isLoading, setIsLoading]       = useState(false);

    const [file, setFile]                       = useState('');
    const [filePhoto, setFilePhoto]             = useState('');
    const [btnDisplay, setbtnDisplay]           = useState('none');
    const [btnDisplayPhoto, setbtnDisplayPhoto] = useState('none');

    const [viewButtonSave, setViewButtonSave]           = useState('none');
    const [viewButtonSavePhoto, setViewButtonSavePHoto] = useState('none');

    const inputFileRef       = React.useRef();
    const inputFileRefPhotos = React.useRef();
    const inputFilePhoto1    = React.useRef();
    const inputFilePhoto2    = React.useRef();

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

        if(String(filePhoto) == 'undefined' || filePhoto == '')
        {
            setViewButtonSavePHoto('none');
        }
        else
        {
            setViewButtonSavePHoto('block');
        }

    }, [filePhoto]);

    useEffect( () => {

        listAllCompany();
        listAllTeam();
        listAllRoute();

        if(auth.idRole == 3)
        {
            listAllDriverByTeam(auth.id);
        }

    }, []);

    useEffect(() => {

        listReportDispatch(1, RouteSearch, StateSearch);

    }, [ idCompany, dateInit, dateEnd, idTeam, idDriver ]);


    const listReportDispatch = (pageNumber, routeSearch, stateSearch) => {

        setIsLoading(true);
        setListReport([]);

        fetch(url_general +'report/list/delivery/'+ idCompany +'/'+ dateInit +'/'+ dateEnd +'/'+ idTeam +'/'+ idDriver +'/'+ routeSearch +'/'+ stateSearch +'?page='+ pageNumber)
        .then(res => res.json())
        .then((response) => {

            setIsLoading(false);
            setListReport(response.reportList);
            setListDeliveries(response.listDeliveries);
            setTotalPackage(response.packageHistoryList.total);
            setTotalPage(response.packageHistoryList.per_page);
            setPage(response.packageHistoryList.current_page);
            setQuantityDispatch(response.packageHistoryList.total);
 
            setRoleUser(response.roleUser);
            setListState(response.listState);

            if(listState.length == 0)
            {
                listOptionState(response.listState);
            }

            // if(response.roleUser == 'Team' || response.roleUser == 'Driver')
            // {
            //     listAllDriverByTeam(idUserGeneral);
            //     setIdTeam(idUserGeneral);
            // }
            // else
            // {
            //     listAllTeam();
            // }
        });
    }

    const listAllCompany = () => {

        setListCompany([]);

        fetch(url_general +'company/getAll')
        .then(res => res.json())
        .then((response) => {

            let CustomListCompany = [{id:0,name:"All companies"},...response.companyList];
            setCompany(0);
            setListCompany(CustomListCompany);

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
        console.log('listando driver por team')
        setListDriver([]);
        setIdTeam(idTeam);
        setIdDriver(0);

        fetch(url_general +'driver/team/list/'+ idTeam)
        .then(res => res.json())
        .then((response) => {

            setListDriver(response.listDriver);
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

    const handlerChangeDateInit = (date) => {

        setDateInit(date);
    }

    const handlerChangeDateEnd = (date) => {

        setDateEnd(date);
    }

    const handlerChangePage = (pageNumber) => {

        listReportDispatch(pageNumber, RouteSearch, StateSearch);
    }

    const handlerExport = (type) => {

        let date1      = moment(dateInit);
        let date2      = moment(dateEnd);
        let difference = date2.diff(date1,'days');

        if(difference > limitToExport)
        {
            swal(`Maximum limit to export is ${limitToExport} days`, {
                icon: "warning",
            });

        }
        else
        {
            let url = url_general +'report/export/delivery/'+ idCompany +'/'+ dateInit +'/'+ dateEnd +'/'+ idTeam +'/'+ idDriver +'/'+ RouteSearch +'/'+ StateSearch +'/'+ type;

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
    }

    const handlerViewMap = (taskOnfleet, arrivalLonLat) => {

        LoadingShowMap();

        if(taskOnfleet != '')
        {
            fetch(url_general +'package-dispatch/getCoordinates/'+ taskOnfleet)
            .then(res => res.json())
            .then((response) => {

                if(response)
                {
                    console.log(response['completionDetails']['lastLocation']);

                    let lastLocation = response['completionDetails']['lastLocation'];
                    let latitude     = lastLocation[1];
                    let longitude    = lastLocation[0];
                    
                    window.open('https://maps.google.com/?q='+ latitude +','+ longitude);
                }
                else
                {
                    swal('Attention!', 'The TASK ONFLEET does not exists', 'warning');
                }

                LoadingHideMap();
            });
        }
        else
        {
            let longitudeLat = arrivalLonLat.split(',');
            let latitude     = longitudeLat[1];
            let longitude    = longitudeLat[0].split('`')[1];
                    
            window.open('https://maps.google.com/?q='+ latitude +','+ longitude);

            LoadingHideMap();
        }
    }

    const listReportTable = listReport.map( (packageDelivery, i) => {

        let imgs = '';
        let urlImage = '';

        let photoHttp = false;

        if(packageDelivery.filePhoto1 == '' && packageDelivery.filePhoto2 == '')
        {
            if(packageDelivery.photoUrl == '')
            {
                if(!packageDelivery.idOnfleet)
                {
                    photoHttp = true;
                }
                else if(packageDelivery.idOnfleet && packageDelivery.photoUrl == '')
                {
                    photoHttp = true;
                }
            }

            if(photoHttp)
            {
                let team     = ''
                let driver   = '';

                listDeliveries.forEach( delivery => {

                    if(packageDelivery.Reference_Number_1 == delivery.taskDetails)
                    {
                        urlImage = delivery.photoUrl;

                        if(urlImage)
                        {
                            urlImage = urlImage.split('https');

                            if(urlImage.length == 2)
                            {
                                imgs = <img src={ 'https'+ urlImage[1] } width="100"/>;
                            }
                            else if(urlImage.length >= 3)
                            {
                                imgs =  <>
                                            <img src={ 'https'+ urlImage[1] } width="50" style={ {border: '2px solid red'} }/>
                                            <img src={ 'https'+ urlImage[2] } width="50" style={ {border: '2px solid red'} }/>
                                        </>
                            }
                        }

                        urlImage = delivery.photoUrl;
                    }
                });

                if(packageDelivery.driver)
                {
                    if(packageDelivery.driver.nameTeam)
                    {
                        team   = packageDelivery.driver.nameTeam;
                        driver = packageDelivery.driver.name +' '+ packageDelivery.driver.nameOfOwner;
                    }
                    else
                    {
                        team   = packageDelivery.driver.name;
                    }
                }
            }
            else if(packageDelivery.idOnfleet && packageDelivery.photoUrl)
            {
                let idsImages = packageDelivery.photoUrl.split(',');

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
            else if(packageDelivery.photoUrl != '')
            {
                alert(packageDelivery.photoUrl);
                let idsImages = packageDelivery.photoUrl.split(',');

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
        else
        {
            if(packageDelivery.filePhoto1 != '' && packageDelivery.filePhoto2 != '')
            {
                imgs =  <>
                            <img src={ url_general +'img/deliveries/'+ packageDelivery.filePhoto1 } width="50" style={ {border: '2px solid red'} }/>
                            <img src={ url_general +'img/deliveries/'+ packageDelivery.filePhoto2 } width="50" style={ {border: '2px solid red'} }/>
                        </>

                urlImage = url_general +'img/deliveries/'+ packageDelivery.filePhoto1 + url_general +'img/deliveries/'+ packageDelivery.filePhoto2

                
            }
            else if(packageDelivery.filePhoto1 != '')
            {
                imgs = <img src={ url_general +'img/deliveries/'+ packageDelivery.filePhoto1 } width="100"/>;

                urlImage = url_general +'img/deliveries/'+ packageDelivery.filePhoto1;
            }
            else if(packageDelivery.filePhoto2 != '')
            {
                imgs = <img src={ url_general +'img/deliveries/'+ packageDelivery.filePhoto2 } width="100"/>;

                urlImage = url_general +'img/deliveries/'+ packageDelivery.filePhoto2;
            }
        }

        let team   = (packageDelivery.team ? packageDelivery.team.name : '');
        let driver = (packageDelivery.driver ? packageDelivery.driver.name +' '+ packageDelivery.driver.nameOfOwner : '');

        console.log(packageDelivery.Reference_Number_1 +': '+ urlImage);

        return (

            <tr key={i}>
                <td style={ { width: '100px'} }>
                    { packageDelivery.Date_Delivery.substring(5, 7) }-{ packageDelivery.Date_Delivery.substring(8, 10) }-{ packageDelivery.Date_Delivery.substring(0, 4) }<br/>
                    { packageDelivery.Date_Delivery.substring(11, 19) }
                </td>
                <td>
                    { packageDelivery.inboundDate.substring(5, 7) }-{ packageDelivery.inboundDate.substring(8, 10) }-{ packageDelivery.inboundDate.substring(0, 4) }<br/>
                    { packageDelivery.inboundDate.substring(11, 19) }
                </td>
                <td><b>{ packageDelivery.company }</b></td>
                <td><b>{ team }</b></td>
                <td><b>{ driver }</b></td>
                <td><b>{ packageDelivery.Reference_Number_1 }</b></td>
                <td>{ packageDelivery.Dropoff_Contact_Name }</td>
                <td>{ packageDelivery.Dropoff_Contact_Phone_Number }</td>
                <td>{ packageDelivery.Dropoff_Address_Line_1 }</td>
                <td>{ packageDelivery.Dropoff_City }</td>
                <td>{ packageDelivery.Dropoff_Province }</td>
                <td>{ packageDelivery.Dropoff_Postal_Code }</td>
                <td>{ packageDelivery.Weight }</td>
                <td>{ packageDelivery.Route }</td>
                <td>
                    { packageDelivery.taskOnfleet }
                    <br/>
                    {
                        ( packageDelivery.taskOnfleet != '' || packageDelivery.arrivalLonLat != '')
                        ?
                            <button className="btn btn-success btn-sm" onClick={ () => handlerViewMap(packageDelivery.taskOnfleet, packageDelivery.arrivalLonLat) }>View Map</button>
                        :
                            ''
                    }
                </td>
                <td onClick={ () => viewImages(urlImage)} style={ {cursor: 'pointer'} }>
                    { imgs }
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

    const handlerChangeRoute = (routes) => {

        if(routes.length != 0)
        {
            let routesSearch = '';

            routes.map( (route) => {

                routesSearch = routesSearch == '' ? route.value : routesSearch +','+ route.value;
            });

            setRouteSearch(routesSearch);

            listReportDispatch(1, routesSearch, StateSearch);
        }
        else
        {
            setRouteSearch('all');

            listReportDispatch(1, 'all', StateSearch);
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

            listReportDispatch(page, RouteSearch, statesSearch);
        }
        else
        {
            setStateSearch('all');

            listReportDispatch(page, RouteSearch, 'all');
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

    const handlerImport = (e) => {

        e.preventDefault();

        const formData = new FormData();

        formData.append('file', file);

        let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        LoadingShow();

        fetch(url_general +'package-delivery/import', {
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

                    listReportDispatch(1, RouteSearch, StateSearch);
                    setViewButtonSave('none');
                }

                LoadingHide();
            },
        );
    }

    const handlerImportPhotos = (e) => {

        e.preventDefault();

        const formData = new FormData();

        formData.append('file', filePhoto);

        let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        LoadingShow();

        fetch(url_general +'package-delivery/import-photo', {
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

                    document.getElementById('fileImportPhoto').value = '';

                    listReportDispatch(1, RouteSearch, StateSearch);
                    setViewButtonSavePHoto('none');
                }

                LoadingHide();
            },
        );
    }

    const onBtnClickFile = () => {

        setViewButtonSave('none');

        inputFileRef.current.click();
    }

    const onBtnClickFilePhotos = () => {

        setViewButtonSave('none');

        inputFileRefPhotos.current.click();
    }

    const [messageUpdateOnfleet, setMessageUpdateOnfleet] = useState('');

    const linkUpdateOnfleet = () => {

        setMessageUpdateOnfleet('Updating onfleet...');

        fetch(url_general +'package-delivery/updatedOnfleet')
        .then(res => res.json()).
        then((response) => {

                if(response.stateAction == 'onfleet')
                {
                    setMessageUpdateOnfleet(response.quantityOnfleet +' packages have been updated as delivered');
                    //swal('Update Complete!', response.quantityOnfleet +' packages have been updated as delivered', 'success');

                    listReportDispatch(1, RouteSearch, StateSearch);
                }

                LoadingHide();
            },
        );
    }

    const handlerUpdateState = () => {

        linkUpdateOnfleet();

        setInterval(linkUpdateOnfleet, 90000);
    }

    const optionCompany = listCompany.map( (company, i) => {

        return <option value={company.id}>{company.name}</option>
    })

    const[urlMap, setUrlMap] = useState('');

    const modalViewMap = <React.Fragment>
                                    <div className="modal fade" id="modalViewMap" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div className="modal-dialog">
                                            <div className="modal-content">
                                                <div className="modal-header">
                                                    <h5 className="modal-title text-primary" id="exampleModalLabel">View Images</h5>
                                                    <button type="button" className="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div className="modal-body">
                                                    <iframe src={ urlMap } frameborder="0" style={ {width: '100%'} }></iframe>
                                                </div>
                                                <div className="modal-footer">
                                                    <button type="button" className="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </React.Fragment>;

    const handlerOpenModalInsertDelivery = (PACKAGE_ID) => {

        let myModal = new bootstrap.Modal(document.getElementById('modalInsertDelivery'), {

            keyboard: false,
            backdrop: 'static',
        });

        myModal.show();
    }

    const handlerInsertDelivery = (e) => {

        e.preventDefault();

        const formData = new FormData();

        formData.append('Reference_Number_1', Reference_Number_1);
        formData.append('idTeam', idTeamDelivery);
        formData.append('filePhoto1', filePhoto1);
        formData.append('filePhoto2', filePhoto2);
        formData.append('DateDelivery', DateDelivery);
        formData.append('HourDelivery', HourDelivery);
        formData.append('arrivalLonLat', arrivalLonLat);

        LoadingShowMap();

        let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        fetch(url_general +'package-delivery/insert', {
            headers: { "X-CSRF-TOKEN": token },
            method: 'post',
            body: formData
        })
        .then(res => res.json()).
        then((response) => {

                if(response.stateAction == true)
                {
                    swal("The package was closed!", {

                        icon: "success",
                    });

                    setReference_Number_1('');
                    setFilePhoto1('');
                    setFilePhoto2('');
                    setDateDelivery('');
                    setHourDelivery('');
                    setArrivalLonLat('');

                    document.getElementById('fileImportPhoto1').value = '';
                    document.getElementById('fileImportPhoto2').value = '';

                    listReportDispatch(1, RouteSearch, StateSearch);
                }
                else if(response.stateAction == 'notExists')
                {
                    swal("The package does not exist!", {

                        icon: "warning",
                    });
                }

                LoadingHideMap();
            },
        );
    }

    const removeFilePhoto1 = () => {

        document.getElementById('fileImportPhoto1').value = '';

        setFilePhoto1();
    };

    const removeFilePhoto2 = () => {

        document.getElementById('fileImportPhoto2').value = '';

        setFilePhoto2();
    };

    const modalInsertDelivery = <React.Fragment>
                                    <div className="modal fade" id="modalInsertDelivery" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div className="modal-dialog modal-md">
                                            <form onSubmit={ handlerInsertDelivery }>
                                                <div className="modal-content">
                                                    <div className="modal-header">
                                                        <h5 className="modal-title text-primary" id="exampleModalLabel">Register Forced Delivery</h5>
                                                        <button type="button" className="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div className="modal-body">
                                                        <div className="row">
                                                            <div className="col-lg-12 mb-2">
                                                                <div className="form-group">
                                                                    <label className="form">PACKAGE ID</label>
                                                                    <div id="Reference_Number_1" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="text" value={ Reference_Number_1 } className="form-control" onChange={ (e) => setReference_Number_1(e.target.value) } maxLength="25" required/>
                                                                </div>
                                                            </div>
                                                            <div className="col-lg-12 mb-2">
                                                                <div className="form-group">
                                                                    <label className="form">TEAM</label>
                                                                    <select name="" id="" className="form-control" onChange={ (e) => setIdTeamDelivery(e.target.value) } required>
                                                                       <option value="0">All</option>
                                                                        { listTeamSelect }
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div className="col-lg-6 mb-2">
                                                                <div className="form-group">
                                                                    <label className="form">PHOTO 1</label>
                                                                    <div id="Photo1" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="file" id="fileImportPhoto1" className="form-control" onChange={ (e) => setFilePhoto1(e.target.files[0]) } accept="image/*"/>
                                                                    {
                                                                        filePhoto1 && (
                                                                            <div style={styles.preview}>
                                                                                <img
                                                                                  src={URL.createObjectURL(filePhoto1)}
                                                                                  style={styles.image}
                                                                                  alt="Thumb"
                                                                                />
                                                                                <button onClick={removeFilePhoto1} style={styles.delete}>
                                                                                    Remove this image
                                                                                </button>
                                                                            </div>
                                                                        )
                                                                    }
                                                                </div>
                                                            </div> 
                                                            <div className="col-lg-6 mb-2">
                                                                <div className="form-group">
                                                                    <label className="form">PHOTO 2</label>
                                                                    <div id="Photo2" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="file" id="fileImportPhoto2" className="form-control" onChange={ (e) => setFilePhoto2(e.target.files[0]) } accept="image/*"/>
                                                                    {
                                                                        filePhoto2 && (
                                                                            <div style={styles.preview}>
                                                                                <img
                                                                                  src={URL.createObjectURL(filePhoto2)}
                                                                                  style={styles.image}
                                                                                  alt="Thumb"
                                                                                />
                                                                                <button onClick={ removeFilePhoto2 } style={ styles.delete }>
                                                                                    Remove this image
                                                                                </button>
                                                                            </div>
                                                                        )
                                                                    }
                                                                </div>
                                                            </div>
                                                            <div className="col-lg-6 mb-2">
                                                                <div className="form-group">
                                                                    <label className="form">DATE</label>
                                                                    <div id="DateDelivery" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="date" value={ DateDelivery } className="form-control" onChange={ (e) => setDateDelivery(e.target.value) } required/>
                                                                </div>
                                                            </div>
                                                            <div className="col-lg-6 mb-2">
                                                                <div className="form-group">
                                                                    <label className="form">HOUR</label>
                                                                    <div id="HourDelivery" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="time" value={ HourDelivery } className="form-control" onChange={ (e) => setHourDelivery(e.target.value) } required/>
                                                                </div>
                                                            </div>
                                                            <div className="col-lg-12 mb-2">
                                                                <div className="form-group">
                                                                    <label className="form">LATITUDE, LONGITUDE: <span className="text-success">-73.69878851,40.732870984</span></label>
                                                                    <div id="HourDelivery" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="text" value={ arrivalLonLat } className="form-control" onChange={ (e) => setArrivalLonLat(e.target.value) }/>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div className="modal-footer">
                                                        <button type="button" className="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                                        <button className="btn btn-primary" disabled={ disabledButton }>Register Return</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </React.Fragment>;

    return (

        <section className="section">
            { modalInsertDelivery }
            { modalViewImages }
            { modalViewMap }
            <div className="row">
                <div className="col-lg-12">
                    <div className="card">
                        <div className="card-body">
                            <h5 className="card-title">
                                <div className="row">
                                    <div className="col-lg-2 mb-3">
                                        <button className="btn btn-success btn-sm form-control" onClick={ () => handlerExport('download') }><i className="ri-file-excel-fill"></i> Export</button>
                                    </div>
                                    <div className="col-3 form-group">
                                        <button className="btn btn-warning btn-sm form-control text-white" onClick={ () => handlerExport('send') }>
                                            <i className="ri-file-excel-fill"></i> EXPORT TO THE MAIL
                                        </button>
                                    </div>
                                    <div className="col-lg-2 mb-3" style={ {display: 'none'} }>
                                        <form onSubmit={ handlerImport }>
                                            <div className="form-group">
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
                                    <div className="col-lg-2 mb-3">
                                        <form onSubmit={ handlerImportPhotos }>
                                            <div className="form-group">
                                                <button type="button" className="btn btn-primary btn-sm form-control" onClick={ () => onBtnClickFilePhotos() }>
                                                    <i className="bx bxs-file"></i> Import
                                                </button>
                                                <input type="file" id="fileImportPhoto" className="form-control" ref={ inputFileRefPhotos } style={ {display: 'none'} } onChange={ (e) => setFilePhoto(e.target.files[0]) } accept=".csv" required/>
                                            </div>
                                            <div className="form-group" style={ {display: viewButtonSavePhoto} }>
                                                <button className="btn btn-primary form-control" onClick={ () => handlerImportPhotos() }>
                                                    <i className="bx  bxs-save"></i> Save
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                    <div className="col-lg-3 mb-3">
                                        <button className="btn btn-info btn-sm form-control text-white" onClick={ () => handlerOpenModalInsertDelivery() }>REGISTER DELIVERY</button>
                                    </div>
                                    <div className="col-lg-2 mb-3 text-warning">
                                        { messageUpdateOnfleet }
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
                                                    <b className="alert-success" style={ {borderRadius: '10px', padding: '10px', fontSize: '14px'} }>Delivery: { quantityDispatch }</b>
                                            )
                                        }
                                    </div>
                                </div>
                                <div className="row form-group">
                                    <div className="col-lg-12 form-group">
                                        <div className="row form-group">
                                            <div className="col-lg-2">
                                                <label htmlFor="">Start date:</label>
                                                <input type="date" value={ dateInit } onChange={ (e) => handlerChangeDateInit(e.target.value) } className="form-control"/>
                                            </div>
                                            <div className="col-lg-2">
                                                <label htmlFor="">End date:</label>
                                                <input type="date" value={ dateEnd } onChange={ (e) => handlerChangeDateEnd(e.target.value) } className="form-control"/>
                                            </div>
                                            <dvi className="col-lg-2">
                                                <div className="row">
                                                    <div className="col-lg-12">
                                                        Company:
                                                    </div>
                                                    <div className="col-lg-12">
                                                        <select name="" id="" className="form-control" onChange={ (e) => setCompany(e.target.value) }>
                                                            <option value="" style={ {display: 'none'} }>Select...</option>
                                                            { optionCompany }
                                                        </select>
                                                    </div>
                                                </div>
                                            </dvi>
                                            {
                                                roleUser == 'Administrador'
                                                ?
                                                    <>
                                                        <div className="col-lg-2">
                                                            <div className="form-group">
                                                                <label htmlFor="">TEAM</label>
                                                                <select name="" id="" className="form-control" onChange={ (e) => listAllDriverByTeam(e.target.value) } required>
                                                                   <option value="0">All</option>
                                                                    { listTeamSelect }
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div className="col-lg-2">
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

                                            <div className="col-lg-2">
                                                <div className="row">
                                                    <div className="col-lg-12">
                                                        State :
                                                    </div>
                                                    <div className="col-lg-12">
                                                        <Select isMulti onChange={ (e) => handlerChangeState(e) } options={ optionsStateSearch } />
                                                    </div>
                                                </div>
                                            </div>
                                            <div className="col-lg-2">
                                                <div className="row">
                                                    <div className="col-lg-12">
                                                        Route :
                                                    </div>
                                                    <div className="col-lg-12">
                                                        <Select isMulti onChange={ (e) => handlerChangeRoute(e) } options={ optionsRoleSearch } />
                                                    </div>
                                                </div>
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
                                                <th>INBOUND DATE</th>
                                                <th>COMPANY</th>
                                                <th><b>TEAM</b></th>
                                                <th><b>DRIVER</b></th>
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
                                                <th>IMAGE</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            { listReportTable }
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

export default ReportDelivery;

if (document.getElementById('reportDelivery'))
{
    ReactDOM.render(<ReportDelivery />, document.getElementById('reportDelivery'));
}

// Just some styles
const styles = {
    container: {
        display: "flex",
        flexDirection: "column",
        justifyContent: "center",
        alignItems: "center",
        paddingTop: 20,
    },
    preview: {
        marginTop: 20,
        display: "flex",
        flexDirection: "column",
    },
    image: { maxWidth: "100%", maxHeight: 320 },
        delete: {
        cursor: "pointer",
        padding: 15,
        background: "red",
        color: "white",
        border: "none",
    },
};