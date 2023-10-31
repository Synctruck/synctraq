import React, { useState, useEffect } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'
import Select from 'react-select'
import moment from 'moment'
import ReactLoading from 'react-loading';

function ReportFailed() {

    const [listReport, setListReport] = useState([]);
    const [listTeam, setListTeam]     = useState([]);
    const [listDriver, setListDriver] = useState([]);
    const [roleUser, setRoleUser]     = useState([]);
    const [listCompany , setListCompany]  = useState([]);
    const [idCompany, setCompany] = useState(0);
    const [listViewImages, setListViewImages] = useState([]);
    const [showModal, setShowModal] = useState(false);
    const [modalImages, setModalImages] = useState([]);

    


    const [quantityDispatch, setQuantityDispatch] = useState(0);

    const [listRoute, setListRoute]  = useState([]);
    const [listState , setListState] = useState([]);

    const [dateInit, setDateInit] = useState(auxDateInit);
    const [dateEnd, setDateEnd]   = useState(auxDateInit);
    const [idTeam, setIdTeam]     = useState(id_team);
    const [idDriver, setIdDriver] = useState(id_driver);

    const [RouteSearch, setRouteSearch] = useState('all');
    const [StateSearch, setStateSearch] = useState('all');

    const [page, setPage]                 = useState(1);
    const [totalPage, setTotalPage]       = useState(0);
    const [totalPackage, setTotalPackage] = useState(0);
    const [isLoading, setIsLoading]       = useState(false);
    const [listStatus, setStatusFailed]   = useState(['OTHER', 'DESTINATION_INCORRECT', 'UNABLE_TO_LOCATE', 'UNAVAILABLE', 'NONE', 'ITEM_INCORRECT', 'DELIVERY_INCIDENT']);

    const [statusDescription, setStatusDescription] = useState('all');

    document.getElementById('bodyAdmin').style.backgroundColor = '#f8d7da';

    useEffect( () => {
        if(auth.idRole == 3){
            listAllDriverByTeam(auth.id);
        }
        listAllTeam();
        listAllRoute();
        listAllCompany();

    }, []);

    useEffect(() => {

        listReportDispatch(1, RouteSearch, StateSearch);

    }, [dateInit, dateEnd, idTeam, idDriver,idCompany, statusDescription]);


    const listReportDispatch = async (pageNumber, routeSearch, stateSearch) => {

        setIsLoading(true);
        setListReport([]);

        const responseData = await fetch(url_general +'report/list/failed/'+ idCompany +'/'+ dateInit +'/'+ dateEnd +'/'+ idTeam +'/'+ idDriver +'/'+ routeSearch +'/'+ stateSearch +'/'+ statusDescription +'?page='+ pageNumber)
        .then(res =>  res.json())
        .then((response) => {

            setIsLoading(false);
            setListReport(response.reportList);
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
        });

        console.log(responseData);
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

    const optionCompany = listCompany.map( (company, i) => {

        return <option value={company.id}>{company.name}</option>
    })

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
            let url = url_general +'report/export/failed/'+ idCompany +'/'+ dateInit +'/'+ dateEnd +'/'+ idTeam +'/'+ idDriver +'/'+ RouteSearch +'/'+ StateSearch +'/'+ statusDescription +'/'+ type;

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


    function handleImageClick(images) {
        setModalImages(images);
        setShowModal(true);
    }


    const baseURL = "https://d15p8tr8p0vffz.cloudfront.net/";

    const listReportTable = listReport.map( (packageDispatch, i) => {

        let team   = (packageDispatch.team ? packageDispatch.team.name : '');
        let driver = (packageDispatch.driver ? packageDispatch.driver.name +' '+ packageDispatch.driver.nameOfOwner : '');
        
        const photoUrls = packageDispatch.photoUrl 
        ? packageDispatch.photoUrl.split(',') 
        : [];

        const viewImageButton = photoUrls.length > 0 ? (
            <button className="btn btn-success btn-sm" onClick={() => handleImageClick(photoUrls.map(url => baseURL + url.trim() + "/800x.png"))}>
                View Images
            </button>
        ) : null;

        return (

            <tr key={i}>
                <td>
                    { packageDispatch.created_at.substring(5, 7) }-{ packageDispatch.created_at.substring(8, 10) }-{ packageDispatch.created_at.substring(0, 4) }
                </td>
                <td>
                    { packageDispatch.created_at.substring(11, 19) }
                </td>
                <td><b>{ packageDispatch.company }</b></td>
                <td><b>{ team }</b></td>
                <td><b>{ driver }</b></td>
                <td><b>{ packageDispatch.Reference_Number_1 }</b></td>
                <td>{ packageDispatch.description }</td>
                <td>{ packageDispatch.status }</td>
                <td>
                    { packageDispatch.statusDate.substring(5, 7) }-{ packageDispatch.statusDate.substring(8, 10) }-{ packageDispatch.statusDate.substring(0, 4) }
                </td>
                <td>{ packageDispatch.statusDescription }</td>
                <td>{ packageDispatch.Dropoff_Contact_Name }</td>
                <td>{ packageDispatch.Dropoff_Contact_Phone_Number }</td>
                <td>{ packageDispatch.Dropoff_Address_Line_1 }</td>
                <td>{ packageDispatch.Dropoff_City }</td>
                <td>{ packageDispatch.Dropoff_Province }</td>
                <td>{ packageDispatch.Dropoff_Postal_Code }</td>
                <td>{ packageDispatch.Weight }</td>
                <td>{ packageDispatch.Route }</td>
                <td>{viewImageButton}</td>
            </tr>
        );
    });

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

    const handlerDownloadOnFleet = () => {

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

        location.href = url_general +'package/download/onfleet/'+ idTeam +'/'+ idDriver +'/'+ type +'/'+ valuesCheck +'/'+ StateSearch +'/day/'+ dateInit +'/'+ dateEnd;
    }

    const listStatusOnfleet = listStatus.map( (status, i) => {

        return(
            <option value={ status }>{ status }</option>
        );
    });
    return (
        
        <section className="section">
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
                                </div>
                                <div className="row">
                                    <div className="col-lg-2 mb-3" style={ {paddingLeft: (isLoading ? '5%' : '')} }>
                                        {
                                            (
                                                isLoading
                                                ? 
                                                    <ReactLoading type="bubbles" color="#A8A8A8" height={20} width={50} />
                                                :
                                                    <b className="alert-success" style={ {borderRadius: '10px', padding: '10px'} }>Failed: { quantityDispatch }</b>
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
                                            <div className="col-lg-2">
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
                                            </div>
                                            <div className="col-lg-2">
                                                <div className="form-group">
                                                    <label htmlFor="">Team</label>
                                                    <select name="" id="" className="form-control" onChange={ (e) => listAllDriverByTeam(e.target.value) } required>
                                                       <option value="0">All</option>
                                                        { listTeamSelect }
                                                    </select>
                                                </div>
                                            </div>
                                            <div className="col-lg-2">
                                                <div className="form-group">
                                                    <label htmlFor="">Driver</label>
                                                    <select name="" id="" className="form-control" onChange={ (e) => setIdDriver(e.target.value) } required>
                                                       <option value="0">All</option>
                                                        { listDriverSelect }
                                                    </select>
                                                </div>
                                            </div>
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

                                            <div className="col-lg-4">
                                                <div className="row">
                                                    <div className="col-lg-12">
                                                        DESCRIPTION ONFLEET:
                                                    </div>
                                                    <div className="col-lg-12">
                                                        <select name="" id="" className="form-control" onChange={ (e) => setStatusDescription(e.target.value) }>
                                                            <option value="all">All</option>
                                                            { listStatusOnfleet }
                                                        </select>
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
                                                <th>DESCRIPTION ONFLEET</th>
                                                <th>ACTUAL STATUS</th>
                                                <th>STATUS DATE</th>
                                                <th>ACTUAL STATUS DESCRIPTION</th>
                                                <th>CLIENT</th>
                                                <th>CONTACT</th>
                                                <th>ADDRESS</th>
                                                <th>CITY</th>
                                                <th>STATE</th>
                                                <th>ZIP C</th>
                                                <th>WEIGHT</th>
                                                <th>ROUTE</th>
                                                <th>IMAGES</th>
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
    <div className="modal" tabIndex="-1" style={{ display: showModal ? "block" : "none" }}>
        <div className="modal-dialog modal-lg">
            <div className="modal-content">
                <div className="modal-header">
                    <div className="left-border"></div>
                    <h5 className="modal-title text-primary" id="exampleModalLabel">View Images</h5>
                    <button type="button" className="btn-close" aria-label="Close" onClick={() => setShowModal(false)}></button>
                </div>
                <div className="modal-body">
                    <div className="image-container">
                        {modalImages.map((imgUrl, index) => (
                            <img key={index} src={imgUrl} alt="Dispatch Image" className="img-thumbnail" />
                        ))}
                    </div>
                </div>
                <div className="modal-footer">
                    <button type="button" className="btn btn-secondary" onClick={() => setShowModal(false)}>Close</button>
                </div>
            </div>
        </div>
    </div>

    <style jsx>{`
        .modal {
            background-color: rgba(0,0,0,0.5);
            outline: none;
        }
        .modal-content {
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            height: 600px;
            max-height: 80%;
            overflow-y: auto; 
        }
        .modal-header {
            background-color: #f5f5f5;
            display: flex;
            align-items: center;
        }
        .left-border {
            width: 5px;
            height: 100%;
            background-color: red;  // Cambia este color según lo desees (rojo/azul)
        }
        .modal-title {
            font-size: 24px;
            font-weight: 500;
            color: #333;
            margin-left: 15px;
        }
        .btn-close {
            background-color: transparent;
            border: none;
            font-size: 24px;
            color: #333;
            margin-left: auto;
        }
        .modal-body {
            padding: 0;
        }
        .image-container {
            display: flex;
            flex-direction: column;  /* Alinea las imágenes verticalmente */
            overflow-y: auto; 
            max-height: 400px; 
        }
        
        .img-thumbnail {
            max-width: 100%;
            height: auto;
            margin-bottom: 10px;  /* Añade un poco de espacio entre cada imagen */
        }
    `}</style>


        </section>
    );
}

export default ReportFailed;

if (document.getElementById('reportFailed'))
{
    ReactDOM.render(<ReportFailed />, document.getElementById('reportFailed'));
}
