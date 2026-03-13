import React, {useEffect, useState} from 'react';
import {View, Text, StyleSheet, ActivityIndicator, ScrollView, TouchableOpacity} from 'react-native';
import SeasonService from '../../services/SeasonService';
import {SeasonDetail} from '../../types';

export default function SeasonDetailScreen({route, navigation}: any) {
    const {id} = route.params;
    const [season, setSeason] = useState<SeasonDetail | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        SeasonService.getSeason(id)
            .then(setSeason)
            .finally(() => setLoading(false));
    }, [id]);

    if (loading) {
        return <ActivityIndicator size="large" style={styles.centered}/>;
    }

    if (!season) {
        return <View style={styles.centered}><Text>Season not found</Text></View>;
    }

    return (
        <ScrollView style={styles.container}>
            <View style={styles.section}>
                <Text style={styles.label}>Season Name</Text>
                <Text style={styles.value}>{season.name}</Text>
            </View>
            <View style={styles.section}>
                <Text style={styles.label}>Dates</Text>
                <Text style={styles.value}>{season.startDate} to {season.endDate}</Text>
            </View>

            <TouchableOpacity
                style={styles.viewEventsButton}
                onPress={() => navigation.navigate('EventList', {seasonId: season.id, seasonName: season.name})}
            >
                <Text style={styles.viewEventsText}>View Events</Text>
            </TouchableOpacity>

            <View style={styles.section}>
                <Text style={styles.label}>Sessions</Text>
                {season.sessions.map((session) => (
                    <View key={session.id} style={styles.sessionItem}>
                        <Text style={styles.sessionName}>{session.name}</Text>
                        <Text style={styles.sessionDate}>Starts: {session.startDate}</Text>
                    </View>
                ))}
            </View>
        </ScrollView>
    );
}

const styles = StyleSheet.create({
    container: {flex: 1, backgroundColor: '#fff', padding: 20},
    centered: {flex: 1, justifyContent: 'center', alignItems: 'center'},
    section: {marginBottom: 20},
    label: {fontSize: 14, color: '#666', marginBottom: 5},
    value: {fontSize: 18, fontWeight: '500'},
    sessionItem: {paddingVertical: 10, borderBottomWidth: 1, borderBottomColor: '#eee'},
    sessionName: {fontSize: 16, fontWeight: '500'},
    sessionDate: {fontSize: 14, color: '#666'},
    viewEventsButton: {
        backgroundColor: '#007AFF',
        padding: 15,
        borderRadius: 8,
        alignItems: 'center',
        marginVertical: 10,
    },
    viewEventsText: {color: '#fff', fontSize: 16, fontWeight: 'bold'},
});
