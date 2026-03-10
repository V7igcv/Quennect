import api from './api'

class MonitorService {
    /**
     * Get complete monitor data for an office
     */
    async getMonitorData(officeId) {
        try {
            const response = await api.get(`/monitor/office/${officeId}`)
            return response.data
        } catch (error) {
            console.error('Error fetching monitor data:', error)
            throw error
        }
    }

    /**
     * Get office details only
     */
    async getOfficeDetails(officeId) {
        try {
            const response = await api.get(`/monitor/office/${officeId}/details`)
            return response.data
        } catch (error) {
            console.error('Error fetching office details:', error)
            throw error
        }
    }

    /**
     * Get current serving only
     */
    async getCurrentServing(officeId) {
        try {
            const response = await api.get(`/monitor/office/${officeId}/current-serving`)
            return response.data
        } catch (error) {
            console.error('Error fetching current serving:', error)
            throw error
        }
    }

    /**
     * Get now serving only
     */
    async getNowServing(officeId) {
        try {
            const response = await api.get(`/monitor/office/${officeId}/now-serving`)
            return response.data
        } catch (error) {
            console.error('Error fetching now serving:', error)
            throw error
        }
    }

    /**
     * Get waiting list only
     */
    async getWaitingList(officeId) {
        try {
            const response = await api.get(`/monitor/office/${officeId}/waiting-list`)
            return response.data
        } catch (error) {
            console.error('Error fetching waiting list:', error)
            throw error
        }
    }
}

export default new MonitorService()