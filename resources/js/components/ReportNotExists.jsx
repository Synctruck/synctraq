import React, { useState, useEffect } from 'react'
import ReactDOM from 'react-dom'
import { Modal } from 'react'
import Pagination from "react-js-pagination"
import swal from 'sweetalert'
import moment from 'moment'


function ReportNotExists() {

    const [listReport, setListReport] = useState([]);

    const [dateInit, setDateInit] = useState(auxDateInit);
    const [dateEnd, setDateEnd]   = useState(auxDateInit);

    useEffect(() => {

        listReportNotExists();

    }, [dateInit, dateEnd]);


    const listReportNotExists = () => {

        fetch(url_general +'report/list/notexists/'+ dateInit +'/'+ dateEnd)
        .then(res => res.json())
        .then((response) => {

            setListReport(response.reportList);
        });
    }

    const handlerChangeDateInit = (date) => {

        setDateInit(date);
    }

    const handlerChangeDateEnd = (date) => {

        setDateEnd(date);
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
            location.href = url_general +'report/export/notexists/'+ dateInit +'/'+ dateEnd;
        }
    }

    const listReportTable = listReport.map( (packageNotExists, i) => {

        return (

            <tr key={i}>

                <td>
                    { packageNotExists.Date_Inbound ? packageNotExists.Date_Inbound.substring(5, 7) +'-'+ packageNotExists.Date_Inbound.substring(8, 10) +'-'+ packageNotExists.Date_Inbound.substring(0, 4) : '' }
                    <br/>
                    { packageNotExists.Date_Inbound ? packageNotExists.Date_Inbound.substring(11, 19) : '' }
                </td>
                <td><b>{ packageNotExists.Reference_Number_1 }</b></td>
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
                                <div className="row form-group">
                                    <div className="col-lg-12 form-group">
                                        <div className="row form-group">
                                            <div className="col-lg-4">
                                                <label htmlFor="">Start date:</label>
                                                <input type="date" value={ dateInit } onChange={ (e) => handlerChangeDateInit(e.target.value) } className="form-control"/>
                                            </div>
                                            <div className="col-lg-4">
                                                <label htmlFor="">End date:</label>
                                                <input type="date" value={ dateEnd } onChange={ (e) => handlerChangeDateEnd(e.target.value) } className="form-control"/>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div className="row">
                                    <div className="col-lg-2">
                                        <b className="alert-success" style={ {borderRadius: '10px', padding: '10px'} }>Inbound: { listReport.length }</b>
                                    </div>
                                    <div className="col-lg-2">
                                        <button className="btn btn-success btn-sm form-control" onClick={ () => handlerExport() }><i className="ri-file-excel-fill"></i> Exportar</button>
                                    </div>
                                </div>
                            </h5>
                            <div className="row form-group table-responsive">
                                <div className="col-lg-12">
                                    <table className="table table-hover table-condensed table-bordered">
                                        <thead>
                                            <tr>
                                                <th>DATE</th>
                                                <th>PACKAGE ID</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            { listReportTable }
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}

export default ReportNotExists;

if (document.getElementById('reportNotExists'))
{
    ReactDOM.render(<ReportNotExists />, document.getElementById('reportNotExists'));
}
