import React, { useState, useEffect } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'
import Select from 'react-select'

function PackageReturn() {

    const [listPackageReturn, setListPackageReturn] = useState([]);
    const [listComment, setListComment]             = useState([]);
    const [roleUser, setRoleUser]                   = useState([]);
    const [listRoute, setListRoute]                 = useState([]);
    const [listState , setListState]                = useState([]);

    const [readOnly, setReadOnly] = useState(false);

    const [Comment, setComment] = useState('');

    const [quantityReturn, setQuantityReturn] = useState(0);

    const [returnReference_Number_1, setReturnNumberPackage] = useState('');
    const [descriptionReturn, setDescriptionReturn] = useState('');

    const [Reference_Number_1, setNumberPackage] = useState('');

    const [showReturnPackage, setShowReturnPackage] = useState('none');
    const [iconReturnPackage, setIconReturnPackage] = useState('bi bi-eye-fill');

    const [textMessage, setTextMessage] = useState('');
    const [typeMessage, setTypeMessage] = useState('');

    const [page, setPage]                 = useState(1);
    const [totalPage, setTotalPage]       = useState(0);
    const [totalPackage, setTotalPackage] = useState(0);

    const [RouteSearch, setRouteSearch] = useState('all');
    const [StateSearch, setStateSearch] = useState('all');

    const [textSearch, setSearch] = useState('');
    const [textButtonSave, setTextButtonSave] = useState('Guardar');

    document.getElementById('bodyAdmin').style.backgroundColor = '#f8d7da';

    useEffect(() => {

        listAllComment(page);
        listAllRoute();

    }, [textSearch])

    useEffect(() => {

        listAllComment();
        listAllPackageReturn(page, RouteSearch, StateSearch);

    }, []);

    const optionsComment = listComment.map( (comment, i) => {

        return (

            <option key={ i } value={ comment.description } > {comment.description}</option>
        );
    });

    const listAllPackageReturn = (pageNumber, route, state) => {

        fetch(url_general +'package/list/return/'+ route +'/'+ state +'?page='+ pageNumber)
        .then(res => res.json())
        .then((response) => {

            setListPackageReturn(response.packageReturnList.data);
            setQuantityReturn(response.quantityReturn);
            setTotalPackage(response.packageReturnList.total);
            setTotalPage(response.packageReturnList.per_page);
            setPage(response.packageReturnList.current_page);
            setListState(response.listState);

            if(listState.length == 0)
            {
                listOptionState(response.listState);
            }
        });
    }

    useEffect(() => {

        listAllComment(page);

    }, [textSearch])

    const listAllComment = (pageNumber) => {

        fetch(url_general +'comments/list?page='+ pageNumber +'&textSearch='+ textSearch)
        .then(res => res.json())
        .then((response) => {

            setListComment(response.commentList.data);
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

        return (

            <tr key={i} className={ packageReturn.statusOnfleet == 3 || packageReturn.statusOnfleet == 1 ? 'alert-warning' : 'alert-danger' }>

                <td style={ { width: '100px'} }>
                    { packageReturn.Date_Return ? packageReturn.Date_Return.substring(5, 7) +'-'+ packageReturn.Date_Return.substring(8, 10) +'-'+ packageReturn.Date_Return.substring(0, 4) : '' }
                </td>
                <td>
                    { packageReturn.Date_Return ? packageReturn.Date_Return.substring(11, 19):'' }
                </td>
                <td>{ packageReturn.team }</td>
                <td>{ packageReturn.workerName }</td>
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
        formData.append('Description_Return', descriptionReturn);

        clearValidation();

        let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        setReadOnly(true);

        fetch(url_general +'package/return/dispatch', {
            headers: { "X-CSRF-TOKEN": token },
            method: 'post',
            body: formData
        })
        .then(res => res.json()).
        then((response) => {

                clearForm();

                if(response.stateAction == true)
                {
                    setTextMessage("Paquete N° "+ returnReference_Number_1 +" fue retornado!");
                    setTypeMessage('success');
                    setNumberPackage('');

                    document.getElementById('return_Reference_Number_1').focus();
                    document.getElementById('soundPitidoSuccess').play();

                    listAllPackageReturn(page, RouteSearch, StateSearch);
                }
                else if(response.stateAction == 'notUser')
                {
                    setTextMessage("El paquete N° "+ returnReference_Number_1 +" fue validado por otro Driver!");
                    setTypeMessage('error');
                    setReturnNumberPackage('');

                    document.getElementById('return_Reference_Number_1').focus();
                    document.getElementById('soundPitidoError').play();
                }
                else if(response.stateAction == 'notDispatch')
                {
                    setTextMessage("El paquete #"+ returnReference_Number_1 +" no fue validado como Dispatch!");
                    setTypeMessage('warning');
                    setNumberPackage('');

                    document.getElementById('return_Reference_Number_1').focus();
                    document.getElementById('soundPitidoWarning').play();
                }
                else if(response.stateAction)
                {
                    setTextMessage("Paquete N° "+ returnReference_Number_1 +" fue retornado!");
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
                                    <div className="col-lg-4">
                                        <div className="form-group">
                                            List of Returned Packages <br/><br/>
                                        </div>
                                    </div>
                                    <div className="col-lg-8 text-center">
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
                                    <div className="col-lg-12">
                                        <form onSubmit={ handlerSaveReturn } autoComplete="off">
                                            <div className="row">
                                                <div className="col-lg-6">
                                                    <div className="form-group">
                                                        <label>PACKAGE ID</label>
                                                        <div id="returnReference_Number_1" className="text-danger" style={ {display: 'none'} }></div>
                                                        <input id="return_Reference_Number_1" type="text" className="form-control" value={ returnReference_Number_1 } onChange={ (e) => setReturnNumberPackage(e.target.value) } maxLength="15" required readOnly={ readOnly }/>
                                                    </div>
                                                </div>
                                                <div className="col-lg-6">
                                                    <div className="form-group">
                                                        <label>RETURN COMMENT</label>
                                                        <div id="descriptionReturn" className="text-danger" style={ {display: 'none'} }></div>
                                                        <select name="" id="" className="form-control" onChange={ (e) => setDescriptionReturn(e.target.value) }>
                                                            <option value="">Selection comment</option>
                                                            { optionsComment }
                                                        </select>
                                                    </div>
                                                    <br/>
                                                </div>
                                            </div>
                                        </form>
                                        <div className="col-lg-2 form-group">
                                            <audio id="soundPitidoSuccess" src="../sound/pitido-success.mp3" preload="auto"></audio>
                                            <audio id="soundPitidoError" src="../sound/pitido-error.mp3" preload="auto"></audio>
                                            <audio id="soundPitidoWarning" src="../sound/pitido-warning.mp3" preload="auto"></audio>
                                        </div>
                                    </div>
                                </div>
                                <div className="row">
                                    <div className="col-lg-2">
                                        <div className="form-group">
                                            <b className="alert-success" style={ {borderRadius: '10px', padding: '10px'} }>Returns: { quantityReturn }</b>
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
                                                <th>TEAM</th>
                                                <th>DRIVER</th>
                                                <th>PACKAGE ID</th>
                                                <th>DESCRIPTION RETURN</th>
                                                <th>DESCRIPTION ONFLEET</th>
                                                <th>CLIENT</th>
                                                <th>CONTACT</th>
                                                <th>ADDREESS</th>
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
