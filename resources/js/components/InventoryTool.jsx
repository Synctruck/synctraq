import React, { useState, useEffect } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'
import Select from 'react-select'
import moment from 'moment';
import ReactLoading from 'react-loading';
import { Tooltip as ReactTooltip } from "react-tooltip";

function InventoryTool() {

    const [listPackage, setListInventory] = useState([]);
    const [pageNumber, setPackageNumber] = useState(1);
    const [listInventoryToolDetailPending, setListInventoryToolDetailPending] = useState([]);
    const [listInventoryToolDetailOverage, setListInventoryToolDetailOverage] = useState([]);
    const [dateInventory, setDateInventory] = useState()

    const [Reference_Number_1, setNumberPackage] = useState('');
    const [idInventory, setIdInventory]          = useState('');

    const [textMessage, setTextMessage] = useState('');
    const [typeMessage, setTypeMessage] = useState('');

    const [divNewInventoryTool, setDivNewInventoryTool] = useState('none')

    const [isLoading, setIsLoading] = useState(false);
    const [dateStart, setDateStart] = useState(auxDateInit);
    const [dateEnd, setDateEnd]     = useState(auxDateInit);

    const [page, setPage]                 = useState(1);
    const [totalPage, setTotalPage]       = useState(0);
    const [totalPackage, setTotalInventory] = useState(0);

    document.getElementById('bodyAdmin').style.backgroundColor = '#fff3cd';

    useEffect(() => {

        listAllInventoryTool();

    }, [dateStart, dateEnd]);

    useEffect(() => {

    }, [Reference_Number_1])

    const listAllInventoryTool = () => {

        setIsLoading(true);
        setListInventory([]);

        fetch(url_general +'inventory-tool/list/'+ dateStart+'/'+ dateEnd +'/?page='+ pageNumber)
        .then(res => res.json())
        .then((response) => {

            setIsLoading(false);
            setListInventory(response.inventoryToolList.data);
            setTotalInventory(response.inventoryToolList.total);
            setTotalPage(response.inventoryToolList.per_page);
            setPage(response.inventoryToolList.current_page);
            setDivNewInventoryTool(response.newInventory);
        });
    }

    const handlerInventoryDetailList = (idInventory) => {

        setIdInventory(idInventory);

        LoadingShowMap()

        let url = url_general +'inventory-tool/list-detail/'+ idInventory;

        fetch(url)
        .then(res => res.json())
        .then((response) => {

            setListInventoryToolDetailPending(response.listInventoryToolDetailPending);
            setListInventoryToolDetailOverage(response.listInventoryToolDetailOverage);

            LoadingHideMap()
        });
    }

    const handlerOpenModal = (idInventory) => {

        let myModal = new bootstrap.Modal(document.getElementById('modalInventoryPackages'), {

            keyboard: true
        });

        myModal.show();

        handlerInventoryDetailList(idInventory)
    }

    const handlerChangePage = (pageNumber) => {

        listAllInventoryTool();
    }

    const [readOnlyInput, setReadOnlyInput]   = useState(false);

    const listInventoryToolDetailPendingTable = listInventoryToolDetailPending.map( (inventoryToolDetail, i) => {
        return (
            <tr>
                <td key={ i }>{ inventoryToolDetail.Reference_Number_1 }</td>
            </tr>
        );
    });

    const listInventoryToolDetailOverageTable = listInventoryToolDetailOverage.map( (inventoryToolDetail, i) => {
        return (
            <tr>
                <td key={ i }>{ inventoryToolDetail.Reference_Number_1 }</td>
            </tr>
        );
    });

    const handlerDownload = (idInventory) => {
        window.open(url_general +'inventory-tool/download/'+ idInventory)
    }

    const handlerInsert = () => {
        LoadingShowMap()

        let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        let url = 'inventory-tool/insert'

        fetch(url_general + url, {
            headers: { "X-CSRF-TOKEN": token },
            method: 'post',
            body: []
        })
        .then(res => res.json())
        .then((response) => {

                if(response.statusCode == true)
                {
                    setIdInventory(response.idInventory)
                    handlerInventoryDetailList(response.idInventory)
                    listAllInventoryTool();

                    swal('The inventory was created!', {

                        icon: "success",
                    });
                }
                else
                {
                    swal('There was an error try again!', {

                        icon: "error",
                    });
                }

                LoadingHideMap()
            },
        );
    }

    const handlerRegisterPackage = (e) => {
        e.preventDefault();

        const formData = new FormData();

        formData.append('Reference_Number_1', Reference_Number_1);
        formData.append('idInventoryTool', idInventory);

        clearValidation();

        setTextMessage('');
        setTypeMessage('');
        setReadOnlyInput(true);

        let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        let url = 'inventory-tool/insert-package'

        fetch(url_general + url, {
            headers: { "X-CSRF-TOKEN": token },
            method: 'post',
            body: formData
        })
        .then(res => res.json()).
        then((response) => {

                if(response.statusCode == true)
                {
                    setTextMessage('Package validate in WAREHOUSE #'+ Reference_Number_1);
                    setTypeMessage('success');

                    handlerInventoryDetailList(idInventory)
                    setNumberPackage('')

                    document.getElementById('soundPitidoSuccess').play();
                }
                else if(response.statusCode == 'notExists')
                {
                    swal('The package does not exist!', {

                        icon: "warning",
                    });

                    setTextMessage('The package does not exist #'+ Reference_Number_1);
                    setTypeMessage('warning');

                    document.getElementById('soundPitidoWarning').play();
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

        document.getElementById('Reference_Number_1').style.display = 'none';
        document.getElementById('Reference_Number_1').innerHTML     = '';
    }

    const clearForm = () => {

        setReference_Number_1('')
        setIdInventory(0)
    }

    const handlerFinishInventoryTool = () => {
        swal({
            title: "You want to finalize the inventory?",
            text: "",
            icon: "warning",
            buttons: true,
            dangerMode: true,
        })
        .then((willDelete) => {

            if(willDelete)
            {
                LoadingShowMap();

                fetch(url_general +'inventory-tool/finish/'+ idInventory)
                .then(response => response.json())
                .then(response => {

                    if(response.statusCode == true)
                    {
                        swal("The inventory was finalized!", {

                            icon: "success",
                        });

                        listAllInventoryTool();

                        document.getElementById('btnCloseModal').click()
                    }

                    LoadingHideMap()
                });
            }
        });
    }

    const modalInventoryPackages = <React.Fragment>
                                    <div className="modal fade" id="modalInventoryPackages" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div className="modal-dialog">
                                            <form onSubmit={ handlerRegisterPackage }>
                                                <div className="modal-content">
                                                    <div className="modal-header">
                                                        <div className="row" style={ {width: '100%'} }>
                                                            <div className="col-lg-10">
                                                                <h5 className="modal-title text-primary" id="exampleModalLabel">Inventory { dateInventory }</h5>
                                                            </div>
                                                            <div className="col-lg-2">
                                                                <button type="button" className="btn btn-success btn-sm form-control" onClick={ () => handlerFinishInventoryTool() }>FINISH</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div className="modal-body">
                                                        <div className="row">
                                                            <div className="col-lg-12 form-group">
                                                                <div className="form-group">
                                                                    <label className="form">PACKAGE ID</label>
                                                                    <div id="Reference_Number_1" className="text-danger" style={ {display: 'none'} }></div>
                                                                    <input type="text" value={ Reference_Number_1 } className="form-control" onChange={ (e) => setNumberPackage(e.target.value) } maxLength="30" readOnly={ readOnlyInput } required/>
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
                                                                    typeMessage == 'warning'
                                                                    ?
                                                                        <h2 className="text-warning">{ textMessage }</h2>
                                                                    :
                                                                        ''
                                                                }
                                                            </div>
                                                            <div className="col-lg-6">
                                                                <table className="table table-hover table-condensed table-bordered">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>PENDING ({ listInventoryToolDetailPending.length })</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        { listInventoryToolDetailPendingTable }
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                            <div className="col-lg-6">
                                                                <table className="table table-hover table-condensed table-bordered">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>
                                                                                OVERAGE ({ listInventoryToolDetailOverage.length }) <i className="bi bi-patch-question-fill text-warning" data-tooltip-id="myTooltipReverted1"></i>
                                                                                <ReactTooltip
                                                                                    id="myTooltipReverted1"
                                                                                    place="top"
                                                                                    variant="dark"
                                                                                    content="Packages that should not be in the warehouse."
                                                                                    style={ {width: '40%'} }
                                                                                  />
                                                                            </th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        { listInventoryToolDetailOverageTable }
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div className="modal-footer">
                                                        <button type="button" id="btnCloseModal" className="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </React.Fragment>;

    const listInventoryTable = listPackage.map( (inventory, i) => {

        return (

            <tr key={i} className={ inventory.status == 'New' ? 'alert-warning' : 'alert-success' }>
                <td>
                    <b>{ inventory.created_at.substring(5, 7) }-{ inventory.created_at.substring(8, 10) }-{ inventory.created_at.substring(0, 4) }</b><br/>
                    { inventory.created_at.substring(11, 19) }
                </td>
                <td><b>{ inventory.userName }</b></td>
                <td>{ inventory.nf }</td>
                <td>{ inventory.ov }</td>
                <td>
                    {
                        inventory.status == 'New'
                        ?
                            <button className="btn btn-primary btn-sm" onClick={ () => handlerOpenModal(inventory.id) } style={ {margin: '3px'}}>
                                <i className="bx bx-edit-alt"></i>
                            </button>
                        :
                            <button className="btn btn-success btn-sm m-1" onClick={ () => handlerDownload(inventory.id) }>
                                <i className="ri-file-excel-fill"></i>
                            </button>
                    }
                </td>
            </tr>
        );
    });

    return (

        <section className="section">
            { modalInventoryPackages }
            <div className="row">
                <div className="col-lg-12">
                    <div className="card">
                        <div className="card-body">
                            <h5 className="card-title">
                                <div className="row form-group">
                                    <div className="row" style={ {display: divNewInventoryTool} }>
                                        <div className="col-2">
                                            <button className="btn btn-primary btn-sm form-control" onClick={ () => handlerInsert() }>
                                                NEW
                                            </button>
                                        </div>
                                    </div>
                                    <audio id="soundPitidoSuccess" src="./sound/pitido-success.mp3" preload="auto"></audio>
                                    <audio id="soundPitidoWarning" src="./sound/pitido-warning.mp3" preload="auto"></audio>
                                </div>
                                <div className="row">
                                    <div className="col-lg-2" style={ {paddingLeft: (isLoading ? '5%' : '')} }>
                                        {
                                            (
                                                isLoading
                                                ? 
                                                    <ReactLoading type="bubbles" color="#A8A8A8" height={20} width={50} />
                                                :
                                                    <b className="alert-success" style={ {borderRadius: '10px', padding: '10px'} }>Inventory Tool: { totalPackage }</b>
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
                                </div>
                            </h5>
                            <div className="row form-group table-responsive">
                                <div className="col-lg-12">
                                    <table className="table table-hover table-condensed table-bordered">
                                        <thead>
                                            <tr>
                                                <th>DATE</th>
                                                <th>USER</th>
                                                <th>NF</th>
                                                <th>OV</th>
                                                <th>REPORT</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            { listInventoryTable }
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

export default InventoryTool;

// DOM element
if (document.getElementById('inventoryTool')) {
    ReactDOM.render(<InventoryTool />, document.getElementById('inventoryTool'));
}