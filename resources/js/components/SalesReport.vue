<template>
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                
                <div class="row g-2 align-items-center">
                    <div class="col">
                        <h1 class="page-title">
                        Sales Report
                        </h1>
                        <p class="card-subtitle mt-2">Select the date range to generate the sales report.</p>
                    </div>
                </div>
                
                
                <form @submit.prevent="generateReport">
                    <div class="input-icon mb-2">
                        <VueDatePicker
                            v-model="selectedDate"
                            :format="format"
                            range
                            placeholder="Select a date range"
                            class="form-control"
                        />
                        <span class="input-icon-addon">
                            <!-- Calendar Icon -->
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                <path d="M4 7a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12z"></path>
                                <path d="M16 3v4"></path>
                                <path d="M8 3v4"></path>
                                <path d="M4 11h16"></path>
                                <path d="M11 15h1"></path>
                                <path d="M12 15v3"></path>
                            </svg>
                        </span>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" :disabled="loading">
                        <div v-if="loading">
                            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                            <span> Loading...</span>
                        </div>
                        <span v-else>Generate Report</span>
                    </button>
                </form>

            <!-- Print PDF Button -->
            <a
                v-if="salesData.length > 0"
                :href="`/admin/sales-report/print/${startDate}/${endDate}`"
                target="_blank"
                class="btn btn-secondary mt-3 me-1"
            >
                Print PDF
            </a>

            <!-- Export XLS Button -->
            <a
                v-if="salesData.length > 0"
                :href="`/admin/sales-report/export-xls/${startDate}/${endDate}`"
                target="_blank"
                class="btn btn-success mt-3"
            >
                Export to XLS
            </a>

            </div>
        </div>
    </div>

    <div class="col-md-12 col-lg-12 mt-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Sales Report</h3>
            </div>
                
            <div class="card-table table-responsive">
                <table class="table table-vcenter">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Branch</th>
                            <th>Total Sales</th>
                            <th>Total Orders</th>
                        </tr>
                    </thead>
                    
                    <tbody v-if="loading">
                        <tr class="text-center">
                            <td colspan="4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="sr-only">Loading...</span>
                                </div>
                                <p class="d-flex align-items-center justify-content-center" style="margin-top: 10px;">Generating Report</p>
                            </td>
                        </tr>
                    </tbody>
                    
                    <tbody v-else>
                        <tr v-if="salesData.length === 0">
                            <td class="text-secondary text-center align-middle" colspan="4">No records found</td>
                        </tr>
                        <tr v-else v-for="(data, index) in salesData" :key="index">
                            <td class="text-secondary">{{ index + 1 }}</td>
                            <td class="text-secondary">{{ data.branch_name }}</td>
                            <td class="text-secondary">{{ formatCurrency(data.total_sales) }}</td>
                            <td class="text-secondary">{{ data.total_orders }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script>
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import moment from 'moment';

export default {
    components: { VueDatePicker },
    
    data() {
        return {
            selectedDate: null,
            format: 'yyyy-MM-dd',
            salesData: [],
            loading: false,
            startDate: '',
            endDate: ''
        };
    },

    methods: {
        generateReport() {
            const [startDate, endDate] = this.selectedDate || [];
            if (!startDate || !endDate) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Date Range Required',
                    text: 'Please select a date range to generate the report.',
                    confirmButtonText: 'OK'
                });
                return;
            }
            this.startDate = moment(startDate).format('YYYY-MM-DD');
            this.endDate = moment(endDate).format('YYYY-MM-DD');

            this.loading = true;
            axios
                .get('/admin/sales-report/get', {
                    params: { start_date: this.startDate, end_date: this.endDate }
                })
                .then(response => {
                    this.salesData = response.data;
                    console.log(this.salesData);
                })
                .catch(error => {
                    console.error(error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to load sales data. Please try again.',
                    });
                })
                .finally(() => {
                    this.loading = false;
                });
        },
                formatCurrency(amount) {
            return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount);
        }
    },
};
</script>
