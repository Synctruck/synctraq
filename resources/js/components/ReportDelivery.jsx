import React, { useState, useEffect } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'
import Select from 'react-select'
import moment from 'moment'

function ReportDelivery() {

    const [listReport, setListReport]         = useState([]);
    const [listDeliveries, setListDeliveries] = useState([]);
    const [listTeam, setListTeam]             = useState([]);
    const [listDriver, setListDriver]         = useState([]);
    const [roleUser, setRoleUser]             = useState([]);
    const [listCompany , setListCompany]      = useState([]);

    const [quantityDispatch, setQuantityDispatch] = useState(0);

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

    const [file, setFile]             = useState('');
    const [btnDisplay, setbtnDisplay] = useState('none');

    const [viewButtonSave, setViewButtonSave] = useState('none');

    const inputFileRef  = React.useRef();

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

        setListReport([]);

        fetch(url_general +'report/list/delivery/'+ idCompany +'/'+ dateInit +'/'+ dateEnd +'/'+ idTeam +'/'+ idDriver +'/'+ routeSearch +'/'+ stateSearch +'?page='+ pageNumber)
        .then(res => res.json())
        .then((response) => {

            setListReport(response.reportList.data);
            setListDeliveries(response.listDeliveries);
            setTotalPackage(response.reportList.total);
            setTotalPage(response.reportList.per_page);
            setPage(response.reportList.current_page);
            setQuantityDispatch(response.reportList.total);

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

    const handlerExport = () => {

        let date1= moment(dateInit);
        let date2 = moment(dateEnd);
        let difference = date2.diff(date1,'days');

        if(difference> limitToExport){
            swal(`Maximum limit to export is ${limitToExport} days`, {
                icon: "warning",
            });
        }else{
            location.href = url_general +'report/export/delivery/'+ dateInit +'/'+ dateEnd +'/'+ idTeam +'/'+ idDriver +'/'+ RouteSearch +'/'+ StateSearch;
        }
    }

    const listReportTable = listReport.map( (packageDelivery, i) => {

        let imgs = '';
        let urlImage = '';

        let photoHttp = false;

        if(!packageDelivery.idOnfleet)
        {
            photoHttp = true;
        }
        else if(packageDelivery.idOnfleet && packageDelivery.photoUrl == '')
        {
            photoHttp = true;
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

        let team   = (packageDelivery.team ? packageDelivery.team.name : '');
        let driver = (packageDelivery.driver ? packageDelivery.driver.name +' '+ packageDelivery.driver.nameOfOwner : '');

        return (

            <tr key={i}>
                <td style={ { width: '100px'} }>
                    { packageDelivery.updated_at.substring(5, 7) }-{ packageDelivery.updated_at.substring(8, 10) }-{ packageDelivery.updated_at.substring(0, 4) }
                </td>
                <td>
                    { packageDelivery.updated_at.substring(11, 19) }
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
                <td>{ packageDelivery.taskOnfleet }</td>
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

                    listAllPackage();
                    setbtnDisplay('none');
                }

                LoadingHide();
            },
        );
    }

    const onBtnClickFile = () => {

        setViewButtonSave('none');

        inputFileRef.current.click();
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

    return (

        <section className="section">
            { modalViewImages }
            <div className="row">
                <div className="col-lg-12">
                    <div className="card">
                        <div className="card-body">
                            <h5 className="card-title">
                                <div className="row">
                                    <div className="col-lg-2 mb-3">
                                        <button className="btn btn-success form-control" onClick={ () => handlerExport() }><i className="ri-file-excel-fill"></i> Export</button>
                                    </div>
                                    <div className="col-lg-2 mb-3">
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
                                        <button className="btn btn-success form-control" onClick={ () => handlerUpdateState() }>Updated Onfleet</button>
                                    </div>
                                    <div className="col-lg-2 mb-3 text-warning">
                                        { messageUpdateOnfleet }
                                    </div>
                                </div>
                                <div className="row">
                                    <div className="col-lg-2 mb-3">
                                        <b className="alert-success" style={ {borderRadius: '10px', padding: '10px', fontSize: '14px'} }>Delivery: { quantityDispatch }</b>
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
                                                <th>HOUR</th>
                                                <th>COMPANY</th>
                                                <th><b>TEAM</b></th>
                                                <th><b>DRIVER</b></th>
                                                <th>PACKAGE ID</th>
                                                <th>CLIENT</th>
                                                <th>CONTACT</th>
                                                <th>ADDREESS</th>
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
