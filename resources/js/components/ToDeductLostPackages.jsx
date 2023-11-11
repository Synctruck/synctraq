import React, { useState, useEffect } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'
import Select from 'react-select'

function ToDeductLostPackages() {

    const [listToReverse, setListToReverse] = useState([]);
    const [listCompany, setListCompany]     = useState([]);
    const [listTeam, setListTeam]           = useState([]);

    const [quantityRevert, setQuantityRevert] = useState(0);
    const [totalDeductLost, setTotalDeductLost] = useState(0);

    const [Reference_1, setReference_1] = useState('');
    const [idTeam, setIdTeam] = useState('');

    const [RouteSearch, setRouteSearch]   = useState('all');
    const [StateSearch, setStateSearch]   = useState('all');

    const [page, setPage]                 = useState(1);
    const [totalPage, setTotalPage]       = useState(0);
    const [totalPackage, setTotalPackage] = useState(0);

    useEffect(() => {

        listToDeductLostPackages(1);
    }, []);


    const listToDeductLostPackages = (pageNumber) => {

        setListToReverse([]);

        fetch(url_general +'to-deduct-lost-packages/list?page='+ pageNumber)
        .then(res => res.json())
        .then((response) => {

            setListToReverse(response.toDeductLostPackagesList.data);
            setTotalPackage(response.toDeductLostPackagesList.total);
            setTotalPage(response.toDeductLostPackagesList.per_page);
            setPage(response.toDeductLostPackagesList.current_page);
            setQuantityRevert(response.toDeductLostPackagesList.total);
            setTotalDeductLost(response.totalDeducts);
        });
    }

    const handlerChangeDateInit = (date) => {

        setDateInit(date);
    }

    const handlerChangeDateEnd = (date) => {

        setDateEnd(date);
    }

    const handlerChangePage = (pageNumber) => {

        listToDeductLostPackages(pageNumber, RouteSearch, StateSearch);
    }

    const listAllTeam = () => {

        LoadingShowMap()

        fetch(url_general +'team/list-all-filter')
        .then(res => res.json())
        .then((response) => {

            $('#modalUpdateTeam').modal('show');

            setListTeam(response.listTeam);
            setIdTeam('')

            LoadingHideMap()
        });
    }

    const listTeamSelect = listTeam.map( (team, i) => {

        return (

            <option value={ team.id } className={ (team.useXcelerator == 1 ? 'text-warning' : '') }>{ team.name }</option>
        );
    });

    const handlerUpdateTeam = (e) => {

        e.preventDefault();

        if(idTeam)
        {
            LoadingShowMap()

            fetch(url_general +'to-deduct-lost-packages/update-team/'+ Reference_1 +'/'+ idTeam)
            .then(response => response.json())
            .then(response => {

                $('#modalUpdateTeam').modal('hide');
                
                if(response.statusCode)
                {
                    swal("The package was assigned to the team!", {

                        icon: "success",
                    });

                    let myModal = new bootstrap.Modal(document.getElementById('modalUpdateTeam'), {
                        keyboard: true
                    });

                    listToDeductLostPackages(page);
                }

                LoadingHideMap()
            });
        }
        else
            swal('Attention', 'Select a team', 'warning');
    }

    const handlerEditPrice = (shipmentId) => {

        let priceToDeduct = prompt("Enter the price of the package #"+ shipmentId);

        if(priceToDeduct != '' && priceToDeduct != null)
        {
            if(isNaN(priceToDeduct))
                swal('Attention', 'Enter only numbers', 'warning');
            else
                hanldlerSaveDeductPrice(shipmentId, priceToDeduct)
        }
        else
        {
            swal('Attention', 'You must enter an amount in the text box', 'warning');
        }
    }

    const hanldlerSaveDeductPrice = (shipmentId, priceToDeduct) => {

        LoadingShowMap();

        fetch(url_general +'to-deduct-lost-packages/update/'+ shipmentId +'/'+ priceToDeduct)
        .then(response => response.json())
        .then(response => {

            if(response.statusCode == true)
            {
                swal('Correct', 'The price was updated correctly', 'success');

                listToDeductLostPackages();
            }
            else if(response.statusCode)
            {
                swal('Attention', 'There was an error, try again', 'warning');
            }

            LoadingHideMap();
        });
    }

    const handlerChangeFormatPrice = (number) => {

        const exp = /(\d)(?=(\d{3})+(?!\d))/g; 
        const rep = '$1,';
        let arr   = number.toString().split('.'); 
        arr[0]    = arr[0].replace(exp,rep);

        return arr[1] ? arr.join('.'): arr[0];
    }

    const listToDeductTable = listToReverse.map( (toDeductLostPackage, i) => {

        let total = (toDeductLostPackage.priceToDeduct ? handlerChangeFormatPrice(toDeductLostPackage.priceToDeduct) : null)

        return (
            <tr key={i}>
                <td style={ { width: '100px'} }>
                    <b>{ toDeductLostPackage.created_at.substring(5, 7) }-{ toDeductLostPackage.created_at.substring(8, 10) }-{ toDeductLostPackage.created_at.substring(0, 4) }</b><br/>
                    { toDeductLostPackage.created_at.substring(11, 19) }
                </td>
                <td><b>{ toDeductLostPackage.shipmentId }</b></td>
                <td className="text-center">
                    {
                        toDeductLostPackage.team
                        ?
                            toDeductLostPackage.team.name
                        :
                            <button className="btn btn-success btn-sm" onClick={ () => handlerOpenModal(toDeductLostPackage.shipmentId) }>
                                <i className="bx bx-edit-alt"></i>
                            </button>
                    }
                </td>
                <td className="text-danger text-right">
                    {
                        toDeductLostPackage.priceToDeduct
                        ?
                            <h5><b>{ total +' $' }</b></h5>
                        :
                            <button className="btn btn-primary btn-sm" onClick={ () => handlerEditPrice(toDeductLostPackage.shipmentId) }>
                                <i className="bx bx-edit-alt"></i>
                            </button>
                    }
                </td>
            </tr>
        );
    });

    const handlerOpenModal = (shipmentId) => {
        setListTeam([]);
        setReference_1(shipmentId)
        listAllTeam();
    }

    const modalUpdateTeam = <React.Fragment>
                                    <div className="modal fade" id="modalUpdateTeam" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div className="modal-dialog">
                                            <form onSubmit={ handlerUpdateTeam }>
                                                <div className="modal-content">
                                                    <div className="modal-header">
                                                        <h5 className="modal-title text-primary" id="exampleModalLabel">Assign Team</h5>
                                                        <button type="button" className="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div className="modal-body">
                                                        <div className="row">
                                                            <div className="col-lg-12 form-group">
                                                                <label className="form">PACKAGE ID</label>
                                                                <div id="Reference_1" className="text-danger" style={ {display: 'none'} }></div>
                                                                <input type="text" className="form-control" value={ Reference_1 } maxLength="100" readOnly required/>
                                                            </div>
                                                            <div className="col-lg-12 form-group">
                                                                <div className="form-group">
                                                                    <label className="form">TEAM</label>
                                                                    <select name="idTeam" id="idTeam" className="form-control" onChange={ (e) => setIdTeam(e.target.value) } required>
                                                                        <option value="">All</option>
                                                                        { listTeamSelect }
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div className="modal-footer">
                                                        <button type="button" className="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        <button className="btn btn-primary">Save</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </React.Fragment>;

    return (

        <section className="section">
            { modalUpdateTeam }
            <div className="row">
                <div className="col-lg-12">
                    <div className="card">
                        <div className="card-body">
                            <h5 className="card-title">
                                <div className="row">
                                    <div className="col-lg-2 mb-3" style={ {display: 'none'} }>
                                        <button className="btn btn-info btn-sm form-control text-white" onClick={ () => handlerOpenModalInsertToReverts() }>REGISTER TO REVERT</button>
                                    </div>
                                </div>
                                <div className="row">
                                    <div className="col-lg-4 mb-3">
                                        <b className="alert-success" style={ {borderRadius: '10px', padding: '10px', fontSize: '14px'} }>Deduct Lost Quantity: { quantityRevert }</b>
                                    </div>
                                    <div className="col-lg-4 mb-3">
                                        <b className="alert-danger" style={ {borderRadius: '10px', padding: '10px', fontSize: '14px'} }>Total Deduct Lost: { totalDeductLost +' $' }</b>
                                    </div>
                                </div>
                            </h5>
                            <div className="row form-group table-responsive">
                                <div className="col-lg-12">
                                    <table className="table table-hover table-condensed table-bordered">
                                        <thead>
                                            <tr>
                                                <th><b>DATE</b></th>
                                                <th><b>PACKAGE ID</b></th>
                                                <th><b>TEAM</b></th>
                                                <th><b>PRICE</b></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            { listToDeductTable }
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

export default ToDeductLostPackages;

if (document.getElementById('toDeductLostPackages'))
{
    ReactDOM.render(<ToDeductLostPackages />, document.getElementById('toDeductLostPackages'));
}
