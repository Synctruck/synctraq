import React, { useState, useEffect } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'
import Select from 'react-select'

function ToDeductLostPackages() {

    const [listToReverse, setListToReverse]         = useState([]);
    const [listCompany, setListCompany]             = useState([]);

    const [quantityRevert, setQuantityRevert] = useState(0);
    const [totalDeductLost, setTotalDeductLost] = useState(0);

    const [dateInit, setDateInit] = useState(auxDateInit);
    const [dateEnd, setDateEnd]   = useState(auxDateInit);
    const [idCompany, setIdCompany] = useState(0);

    const [Reference_Number_1, setReference_Number_1] = useState('');

    const [RouteSearch, setRouteSearch]   = useState('all');
    const [StateSearch, setStateSearch]   = useState('all');
    const [Status, setStatus]             = useState('all');

    const [page, setPage]                 = useState(1);
    const [totalPage, setTotalPage]       = useState(0);
    const [totalPackage, setTotalPackage] = useState(0);

    useEffect(() => {

        listToDeductLostPackages(1, RouteSearch, Status);

    }, [dateInit, dateEnd, idCompany, Status]);


    const listToDeductLostPackages = (pageNumber, routeSearch, status) => {

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
            swal('Attention', 'You have not entered anything. Select the status again', 'warning');
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
                <td className="text-danger text-right">
                    {
                        toDeductLostPackage.priceToDeduct
                        ?
                            <h5><b>{ total +' $' }</b></h5>
                        :
                            ''
                    }
                </td>
                <td>
                    {
                        !toDeductLostPackage.priceToDeduct
                        ?
                            <button className="btn btn-primary btn-sm" onClick={ () => handlerEditPrice(toDeductLostPackage.shipmentId) }>
                                <i className="bx bx-edit-alt"></i>
                            </button>
                        :
                            ''
                    }
                </td>
            </tr>
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
                                                <th><b>PAYMENT N°</b></th>
                                                <th><b>ACTION</b></th>
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
