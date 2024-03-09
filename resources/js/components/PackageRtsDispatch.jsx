import React, { useState, useEffect } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'
import Select from 'react-select'
import moment from 'moment';
import ReactLoading from 'react-loading';

let count = 1;

function PackageRtsDispatch() {

    const [readOnlyPalet, setReadOnlyPalet] = useState(false);
    const [readOnly, setReadOnly]           = useState(false);
    const [checkAll, setCheckAll]           = useState(0);
    const [isLoading, setIsLoading]             = useState(false);

    const [viewButtonSave, setViewButtonSave] = useState('none');
    const [listPallet, setListPallet] = useState([]);
    const [truckList, setTruckList] = useState([]);
    const [dateStart, setDateStart] = useState(auxDateInit);
    const [dateEnd, setDateEnd] = useState(auxDateEnd);
    const [PalletNumberForm, setPalletNumberForm] = useState('');
    const [Reference_Number_1, setReference_Number_1] = useState('');
    const [typeMessageDispatch, setTypeMessageDispatch] = useState('');
    const [textMessage, setTextMessage]                 = useState('');
    const [textMessageDate, setTextMessageDate] = useState('');

    const [page, setPage]                             = useState(1);
    const [totalPagePallet, setTotalPagePallet]       = useState(0);
    const [totalPackagePallet, setTotalPackagePallet] = useState(0);
    const [totalPage, setTotalPage]                   = useState(0);
    const [totalPackage, setTotalPackage]             = useState(0);

    document.getElementById('bodyAdmin').style.backgroundColor = '#d1e7dd';

    useEffect(() => {

        setPage(1);

        listAllTruck(1);

    }, [ dateStart, dateEnd ]);

    const listAllTruck = (pageNumber) => {

        setIsLoading(true);

        fetch(url_general +'package-pre-rts/dispatch/list-truck/'+ dateStart +'/'+ dateEnd +'/?page='+ pageNumber)
        .then(res => res.json())
        .then((response) => {

            setIsLoading(false);
            setTruckList(response.truckList.data);
            setTotalPackagePallet(response.truckList.total);
            setTotalPagePallet(response.truckList.per_page);
            setPage(response.truckList.current_page);
        });
    }

    const exportAllPackageDispatch = () => {
 
        location.href = url_general +'pallet-rts/export/'+ idCompany +'/'+ dateStart +'/'+ dateEnd;
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

        exportAllPackageDispatch();
    }

    const handlerChangePage = (pageNumber) => {

        listAllPackageDispatch(pageNumber, StateSearch, RouteSearchList);
    }

    const [readOnlyInput, setReadOnlyInput]   = useState(false);
    const [disabledButton, setDisabledButton] = useState(false);

    const handlerClosePallete = () => {

        swal({
            title: "Want to close the palette?",
            text: "",
            icon: "warning",
            buttons: true,
            dangerMode: true,
        })
        .then((willDelete) => {

            if(willDelete)
            {
                const formData = new FormData();

                formData.append('numberPallet', PalletNumberForm);

                let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                let url = 'package-pre-rts/close';

                fetch(url_general + url, {
                    headers: { "X-CSRF-TOKEN": token },
                    method: 'post',
                    body: formData
                })
                .then(res => res.json())
                .then((response) => {

                    if(response.stateAction == true)
                    {
                        swal("The palette was closed correctly!", {

                            icon: "success",
                        });

                        listAllPalet(page);
                        listPackagePreDispatch(PalletNumberForm);
                    }
                    else
                    {
                        swal("There was a problem trying to close the palette, please try again!", {

                            icon: "warning",
                        });
                    }
                });
            }
        });
    }

    const listPalletDispatchTable = listPallet.map( (pallet, i) => {

        return (

            <tr key={i}>
                <td style={ { width: '100px'} }>
                    { pallet.created_at.substring(5, 7) }-{ pallet.created_at.substring(8, 10) }-{ pallet.created_at.substring(0, 4) }
                </td>
                <td>
                    { pallet.created_at.substring(11, 19) }
                </td>
                <td><b>{ pallet.number }</b></td>
                <td>{ pallet.company }</td>
            </tr>
        );
    });


    const [bolNumber, setBolNumber] = useState('');
    const [carrier, setCarrier] = useState('');
    const [driver, setDriver] = useState('');
    const [displayScanPallet, setDisplayScanPallet] = useState('Old');

    const handlerDispatchPallet = (e) => {
        e.preventDefault();

        const formData = new FormData();
        formData.append('numberPallet', PalletNumberForm);
        formData.append('companyNameOrigin', companyNameOrigin);
        formData.append('companyAddressOrigin', companyAddressOrigin);
        formData.append('companyAddressDestination', companyAddressDestination);
        formData.append('driverFullName', driverFullName);

        let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        LoadingShowMap();

        fetch(url_general + 'package-pre-rts/chage-to-return-company', {
            headers: { "X-CSRF-TOKEN": token },
            method: 'post',
            body: formData
        })
        .then(res => res.json())
        .then((response) => {

            if(response.stateAction == true)
            {
                swal("The palette was dispatched correctly!", {

                    icon: "success",
                });

                listAllPalet(page);
                listPackagePreDispatch(PalletNumberForm);
                
                document.getElementById('closeModalDispach').click();
            }
            else
            {
                swal("There was a problem trying to dispatched the palette, please try again!", {

                    icon: "warning",
                });
            }

            LoadingHideMap();
        });
    }

    const handlerOpenModalCreateTruck = (display) => {
        setDisplayScanPallet(display)

        let myModal = new bootstrap.Modal(document.getElementById('modalCreateDispatch'), {

            keyboard: true
        });

        myModal.show();
    }

    const handlerCreateTruck = (e) => {
        e.preventDefault();

        LoadingShowMap();

        const formData = new FormData();
        formData.append('bolNumber', bolNumber);
        formData.append('carrier', carrier);
        formData.append('driver', driver);

        let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        let url = 'package-pre-rts/dispatch/create-truck';

        fetch(url_general + url, {
            headers: { "X-CSRF-TOKEN": token },
            method: 'post',
            body: formData
        })
        .then(res => res.json())
        .then((response) => {

            if(response.stateAction == true)
            {
                swal("The truck was created successfully!", {

                    icon: "success",
                });

                setDisplayScanPallet('Old');

                listAllTruck(1);
            }
            else if(response.stateAction == 'bolExists')
            {
                swal("BOL N° "+ bolNumber +" already exists!", {

                    icon: "warning",
                });
            }
            else
            {
                swal("There was a problem trying to create the truck, please try again!", {

                    icon: "warning",
                });
            }

            LoadingHideMap();
        });
    }

    const modalCreateDispatch = <React.Fragment>
                                    <div className="modal fade" id="modalCreateDispatch" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div className="modal-dialog modal-lg">
                                            <div className="modal-content">
                                                <div className="modal-header">
                                                    <h5 className="modal-title text-primary" id="exampleModalLabel">
                                                        TRUCK - BOL N°: <span className="text-success">{ bolNumber }</span>
                                                    </h5>
                                                    <button type="button" className="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div className="modal-body">
                                                    <form onSubmit={ handlerCreateTruck }>
                                                        <div className="row">
                                                            <div className="col-lg-12">
                                                                <div className="form-group">
                                                                    <label className="form">BOL N°</label>
                                                                    <input type="text" value={ bolNumber } onChange={ (e) => setBolNumber(e.target.value) } className="form-control" minLength="5" maxLength="50" required/>
                                                                </div>
                                                            </div>
                                                            <div className="col-lg-12">
                                                                <div className="form-group">
                                                                    <label className="form">Carrier</label>
                                                                    <input type="text" value={ carrier } onChange={ (e) => setCarrier(e.target.value) } className="form-control" minLength="5" maxLength="150" required/>
                                                                </div>
                                                            </div>
                                                            <div className="col-lg-12">
                                                                <div className="form-group">
                                                                    <label className="form">Driver: Full Name</label>
                                                                    <input type="text" value={ driver } onChange={ (e) => setDriver(e.target.value) } className="form-control" minLength="5" maxLength="200" required/>
                                                                </div>
                                                            </div>
                                                            <div className="col-lg-3" style={ {display: (displayScanPallet == 'Old' ? 'none' : 'block')} }>
                                                                <div className="form-group">
                                                                    <button className="btn btn-primary form-control">Create Truck</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </form>

                                                    <div className="row" style={ {display: (displayScanPallet == 'New' ? 'none' : 'block')} }>
                                                        <div className="col-lg-12 mb-2">
                                                            <form onSubmit={ (e) => handlerValidation(e) } autoComplete="off">
                                                                <div className="row">
                                                                    <div className="col-lg-12">
                                                                        <div className="form-group">
                                                                            <label htmlFor="" className="form">PALLET ID</label>
                                                                            <input id="PalletNumberForm" type="text" className="form-control" value={ PalletNumberForm } onChange={ (e) => setPalletNumberForm(e.target.value) } maxLength="24" required readOnly={ readOnly }/>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                    <div className="row" style={ {display: (displayScanPallet == 'New' ? 'none' : 'block')} }>
                                                        <div className="col-lg-12 text-center mb-2">
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
                                                        <div className="col-lg-12 form-group">
                                                            <audio id="soundPitidoSuccess" src="./sound/pitido-success.mp3" preload="auto"></audio>
                                                            <audio id="soundPitidoError" src="./sound/pitido-error.mp3" preload="auto"></audio>
                                                            <audio id="soundPitidoWarning" src="./sound/pitido-warning.mp3" preload="auto"></audio>
                                                            <audio id="soundPitidoBlocked" src="./sound/pitido-blocked.mp3" preload="auto"></audio>
                                                        </div>
                                                    </div>
                                                    <div className="row table-responsive" style={ {display: (displayScanPallet == 'New' ? 'none' : 'block')} }>
                                                        <div className="col-lg-12">
                                                            <table className="table table-hover table-condensed">
                                                                <thead>
                                                                    <tr>
                                                                        <th>DATE</th>
                                                                        <th>HOUR</th>
                                                                        <th>PALLET ID</th>
                                                                        <th>COMPANY</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    { listPalletDispatchTable }
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div className="modal-footer" style={ {display: (displayScanPallet == 'New' ? 'none' : 'block')} }>
                                                    <div className="row" style={ {width: '100%'} }>
                                                        <div className="col-lg-4">
                                                        </div>
                                                        <div className="col-lg-4">
                                                        </div>
                                                        <div className="col-lg-4">
                                                            <div className="form-group">
                                                                <label className="text-white">---</label>
                                                                <button type="button" className="btn btn-success form-control" onClick={ () => handlerClosePallete () }>
                                                                    Complete Pallet
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </React.Fragment>;

    const [sendDispatch, setSendDispatch] = useState(1);

    const handlerValidation = (e) => {

        e.preventDefault();

        setTextMessage('');

        setReadOnly(true);
        setSendDispatch(0);

        const formData = new FormData();

        formData.append('bolNumber', bolNumber);
        formData.append('numberPallet', PalletNumberForm);

        let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        fetch(url_general +'package-pre-rts/dispatch/insert-pallet-to-truck', {
            headers: { "X-CSRF-TOKEN": token },
            method: 'post',
            body: formData
        })
        .then(res => res.json()).
        then((response) => {

                if(response.stateAction == 'palletIdExistsOtherTruck')
                {
                    setTextMessage('The Pallet ID is already registered in the Truck #'+ response.bolNumber);
                    setTypeMessageDispatch('warning');

                    document.getElementById('soundPitidoWarning').play();
                }
                else if(response.stateAction == 'notExists')
                {
                    setTextMessage('The Pallet ID does not exists #'+ PalletNumberForm);
                    setTypeMessageDispatch('warning');

                    document.getElementById('soundPitidoWarning').play();
                }
                else if(response.stateAction == true)
                {
                    setTextMessage("SUCCESSFULLY PALLET ID #"+ PalletNumberForm);
                    setTextMessageDate('');
                    setTypeMessageDispatch('success');
                    setPalletNumberForm('');

                    handlerListPallets(bolNumber);

                    document.getElementById('PalletNumberForm').focus();
                    document.getElementById('soundPitidoSuccess').play();
                }
                else
                {
                    setTextMessage("A problem has occurred, please try again");
                    setTypeMessageDispatch('error');
                    
                    document.getElementById('PalletNumberForm').focus();
                    document.getElementById('soundPitidoError').play();
                }

                setReadOnly(false);
                setSendDispatch(1);
            },
        );
    }

    const listPackagePreDispatch = (palletNumber) => {

        fetch(url_general +'package-pre-rts/list/'+ palletNumber)
        .then(res => res.json())
        .then((response) => {

            setListPackage(response.packagePreRtsList);
            setFilterDispatch(response.palletRts.status);
        });
    }

    const handlerValidationPallet = (e) => {

        e.preventDefault();
    
        listPackagePreDispatch(PalletNumberForm);
        handlerOpenModalPackage();
    }

    const handlerListPallets = (bolNumber) => {
        fetch(url_general +'package-pre-rts/dispatch/get-truck/'+ bolNumber)
        .then(res => res.json())
        .then((response) => {
            setBolNumber(response.truck.bolNumber);
            setCarrier(response.truck.carrier);
            setDriver(response.truck.driver);
            setListPallet(response.palletList);
        });
    }

    const handlerViewTruck = (bolNumber) => {
        handlerListPallets(bolNumber);
        handlerOpenModalCreateTruck('Old');
    }

    const truckListTable = truckList.map( (truck, i) => {

        return (

            <tr key={i}>
                <td style={ { width: '100px'} }>
                    <b>{ truck.created_at.substring(5, 7) }-{ truck.created_at.substring(8, 10) }-{ truck.created_at.substring(0, 4) }</b><br/>
                    { truck.created_at.substring(11, 19) }
                </td>
                <td><b>{ truck.bolNumber }</b></td>
                <td><b>{ truck.carrier }</b></td>
                <td><b>{ truck.driver }</b></td>
                <td>
                    {
                        (
                            truck.status == 'Peding'
                            ?
                                <button className="alert alert-success font-weight-bold">{ truck.status }</button>
                            :
                                <button className="alert alert-danger font-weight-bold">{ truck.status }</button>
                        )
                    }
                </td>
                <td>
                    <button className="btn btn-primary btn-sm mt-2" onClick={ () => handlerViewTruck(truck.bolNumber) }>
                        View Truck
                    </button>
                </td>
            </tr>
        );
    });

    return (

        <section className="section">
            { modalCreateDispatch }
            <div className="row">
                <div className="col-lg-12">
                    <div className="card">
                        <div className="card-body">
                            <h5 className="card-title">
                                <div className="row form-group">
                                    <div className="col-lg-12">
                                        <div className="row form-group">
                                            <div className="col-lg-2">
                                                <div className="form-group">
                                                    <button className="btn btn-success btn-sm form-control" onClick={  () => handlerExport() }>
                                                        <i className="ri-file-excel-fill"></i> EXPORT
                                                    </button>
                                                </div>
                                            </div>
                                            <div className="col-lg-2">
                                                <div className="form-group">
                                                    <button className="btn btn-primary btn-sm form-control" onClick={  () => handlerOpenModalCreateTruck('New') }>
                                                        CREATE TRUCK
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div className="row">
                                    <div className="col-lg-12 mb-3">
                                        <form onSubmit={ (e) => handlerValidationPallet(e) } autoComplete="off">
                                            <div className="form-group">
                                                <label htmlFor="">BOL N°</label>
                                                <input id="PalletNumberForm" type="text" className="form-control" placeholder="Search BOL..." value={ PalletNumberForm } onChange={ (e) => setPalletNumberForm(e.target.value) } maxLength="30" required readOnly={ readOnlyPalet }/>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <div className="row">
                                    <div className="col-lg-4 mb-3" style={ {paddingLeft: (isLoading ? '5%' : '')} }>
                                        {
                                            (
                                                isLoading
                                                ? 
                                                    <ReactLoading type="bubbles" color="#A8A8A8" height={20} width={50} />
                                                :
                                                    <b className="alert alert-success" style={ {borderRadius: '10px', padding: '10px'} }>TRUCKS: { totalPackagePallet }</b>
                                            )
                                        }
                                    </div>
                                </div>
                                <div className="row">
                                    <div className="col-lg-2 mb-2">
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
                                    <div className="col-lg-2 mb-2">
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
                                </div>
                            </h5>
                            <div className="row form-group table-responsive">
                                <div className="col-lg-12">
                                    <table className="table table-hover table-condensed table-bordered">
                                        <thead>
                                            <tr>
                                                <th>DATE</th>
                                                <th>TRUCK ID</th>
                                                <th>CARRIER</th>
                                                <th>DRIVER</th>
                                                <th>STATUS</th>
                                                <th>ACTION</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            { truckListTable }
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div className="col-lg-12">
                                <Pagination
                                    activePage={page}
                                    totalItemsCount={ totalPackagePallet }
                                    itemsCountPerPage={ totalPagePallet }
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

export default PackageRtsDispatch;

// DOM element
if (document.getElementById('packageRtsDispatch')) {
    ReactDOM.render(<PackageRtsDispatch />, document.getElementById('packageRtsDispatch'));
}