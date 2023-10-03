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

    const [listPackage, setListPackage] = useState([]);
    const [listPackageTotal, setListPackageTotal]     = useState([]);
    const [pageNumber, setPackageNumber] = useState(1);
    const [listRoute, setListRoute]     = useState([]);
    const [dateInventory, setDateInventory] = useState(dateGeneral);
    const [quantityInbound, setQuantityInbound] = useState(0);

    const [Reference_Number_1, setNumberPackage] = useState('');
    const [idInventory, setIdInventory]          = useState('');

    const [textMessage, setTextMessage]         = useState('');
    const [textMessage2, setTextMessage2]       = useState('');
    const [textMessageDate, setTextMessageDate] = useState('');
    const [typeMessage, setTypeMessage]         = useState('');

    const [listInbound, setListInbound] = useState([]);

    const [file, setFile]             = useState('');

    const [displayButton, setDisplayButton] = useState('none');

    const [disabledInput, setDisabledInput] = useState(false);

    const [readInput, setReadInput] = useState(false);
    const [isLoading, setIsLoading] = useState(false);
    const [dateStart, setDateStart] = useState(auxDateInit);
    const [dateEnd, setDateEnd]   = useState(auxDateInit);

    const [page, setPage]                 = useState(1);
    const [totalPage, setTotalPage]       = useState(0);
    const [totalPackage, setTotalPackage] = useState(0);

    const inputFileRef  = React.useRef();

    const [viewButtonSave, setViewButtonSave] = useState('none');

    document.getElementById('bodyAdmin').style.backgroundColor = '#fff3cd';

    useEffect(() => {

        listAllInventoryTool();

    }, [dateStart,dateEnd]);

    useEffect(() => {

    }, [Reference_Number_1])

    const listAllInventoryTool = () => {

        setIsLoading(true);
        setListPackage([]);

        fetch(url_general +'inventory-tool/list/'+ dateStart+'/'+ dateEnd +'/?page='+ pageNumber)
        .then(res => res.json())
        .then((response) => {

            setIsLoading(false);
            setListPackage(response.inventoryToolList.data);
            setTotalPackage(response.inventoryToolList.total);
            setTotalPage(response.inventoryToolList.per_page);
            setPage(response.inventoryToolList.current_page);
            setQuantityInbound(response.quantityInbound);
        });
    }

    const exportAllPackageInbound = (route, state, type) => {

        let url = url_general +'package-lm-carrier/export/'+idCompany+'/'+ dateStart+'/'+ dateEnd +'/'+ route +'/'+ state +'/'+type;

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

    const handlerOpenModal = (idInventory) => {

        let myModal = new bootstrap.Modal(document.getElementById('modalInventoryPackages'), {

            keyboard: true
        });

        myModal.show();
    }

    const handlerExport = (type) => {

        exportAllPackageInbound(RouteSearch, StateSearch, type);
    }

    const handlerChangePage = (pageNumber) => {

        listAllInventoryTool(pageNumber, RouteSearch, StateSearch);
    }

    const [readOnlyInput, setReadOnlyInput]   = useState(false);

    const optionsRole = listRoute.map( (route, i) => {

        return (

            <option key={ i } value={ route.name } selected={ Route == route.name ? true : false }> {route.name}</option>
        );
    });

    const handlerDownload = (PACKAGE_ID) => {

        fetch(url_general +'package-lm-carrier/get/'+ PACKAGE_ID)
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

        let myModal = new bootstrap.Modal(document.getElementById('modalInventoryPackages'), {

            keyboard: true
        });

        myModal.show();
    }

    const handlerInsert = (e) => {
        e.preventDefault();

        LoadingShowMap()

        let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        let url = 'inventory-tool/insert'

        fetch(url_general + url, {
            headers: { "X-CSRF-TOKEN": token },
            method: 'post',
            body: formData
        })
        .then(res => res.json()).
        then((response) => {

                swal('The inventory was created!', {

                    icon: "success",
                });

                LoadingHideMap()
                listAllInventoryTool();
            },
        );
    }

    const handlerRegisterPackage = (e) => {

        e.preventDefault();

        const formData = new FormData();

        formData.append('Reference_Number_1', Reference_Number_1);
        formData.append('idInventory', idInventory);

        clearValidation();

        let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        let url = 'inventory-tool/insert-package'

        fetch(url_general + url, {
            headers: { "X-CSRF-TOKEN": token },
            method: 'post',
            body: formData
        })
        .then(res => res.json()).
        then((response) => {

                if(response.stateAction)
                {
                    swal('Se actualizó el Package!', {

                        icon: "success",
                    });

                    listAllInventoryTool(1, RouteSearch, StateSearch);
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
                                                                <button className="btn btn-success btn-sm form-control">FINISH</button>
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
                                                            <div className="col-lg-6">
                                                                <table className="table table-hover table-condensed table-bordered">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>PENDING</th>
                                                                        </tr>
                                                                    </thead>
                                                                </table>
                                                            </div>
                                                            <div className="col-lg-6">
                                                                <table className="table table-hover table-condensed table-bordered">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>
                                                                                OVERAGE <i className="bi bi-patch-question-fill text-danger" data-tooltip-id="myTooltipReverted1"></i>
                                                                                <ReactTooltip
                                                                                    id="myTooltipReverted1"
                                                                                    place="top"
                                                                                    variant="dark"
                                                                                    content="Reverted shipments are packages 
                                                                                            that were paid in error to the carrier and that 
                                                                                            were marked for a discount on the next invoice. 
                                                                                            The packages shown here are not discounts on the invoice,
                                                                                             it is only to control which packages within this invoice are not valid."
                                                                                    style={ {width: '40%'} }
                                                                                  />
                                                                            </th>
                                                                        </tr>
                                                                    </thead>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div className="modal-footer">
                                                        <button type="button" className="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </React.Fragment>;

    const listPackageTable = listPackage.map( (inventory, i) => {

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
                    <button className="btn btn-primary btn-sm" onClick={ () => handlerOpenModal(inventory.id) } style={ {margin: '3px'}}>
                        <i className="bx bx-edit-alt"></i>
                    </button>
                    <button className="btn btn-success btn-sm m-1" onClick={ () => handlerDownload(inventory.id) }>
                        <i className="ri-file-excel-fill"></i>
                    </button>
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
                                    <div className="row">
                                        <div className="col-2 form-group">
                                            <button className="btn btn-primary btn-sm form-control" onClick={  () => handlerInsert() }>
                                                NEW
                                            </button>
                                        </div>
                                    </div>
                                    <div className="col-12 mb-4">
                                        <div className="row" style={ {display: 'none'} }>
                                            <div className="col-lg-2">
                                                <div className="form-group">
                                                    <button className="btn btn-danger btn-sm form-control" onClick={ () => handlerDownloadRoadWarrior() }>ROADW</button>
                                                </div> 
                                            </div>
                                            <div className="col-2">
                                                <button className="btn btn-success btn-sm form-control" onClick={  () => handlerExport('download') }>
                                                    <i className="ri-file-excel-fill"></i> EXPORT
                                                </button>
                                            </div>
                                            <div className="col-3">
                                                <div className="row">
                                                    <div className="col-12">
                                                        <button className="btn btn-warning btn-sm form-control text-white" onClick={  () => handlerExport('send') }>
                                                            <i className="ri-file-excel-fill"></i> EXPORT TO THE MAIL
                                                        </button>
                                                    </div>
                                                </div>
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

export default InventoryTool;

// DOM element
if (document.getElementById('inventoryTool')) {
    ReactDOM.render(<InventoryTool />, document.getElementById('inventoryTool'));
}