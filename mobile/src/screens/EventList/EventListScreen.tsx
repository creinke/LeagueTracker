import React, {useEffect, useState} from 'react';
import {View, Text, FlatList, TouchableOpacity, StyleSheet, ActivityIndicator, SectionList} from 'react-native';
import EventService from '../../services/EventService';
import {SessionWithEvents, Event} from '../../types';

const EventListScreen = ({route, navigation}: any) => {
    const {seasonId, seasonName} = route.params;
    const [sessions, setSessions] = useState<SessionWithEvents[]>([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        navigation.setOptions({title: seasonName || 'Events'});
        loadEvents();
    }, [seasonId]);

    const loadEvents = async () => {
        try {
            const data = await EventService.getEventsBySeason(seasonId);
            setSessions(data);
        } catch (error) {
            console.error('Failed to load events', error);
        } finally {
            setLoading(false);
        }
    };

    const renderEvent = ({item}: { item: Event }) => (
        <TouchableOpacity
            style={styles.eventItem}
            onPress={() => navigation.navigate('EventDetail', {id: item.id, eventNumber: item.eventNumber})}
        >
            <View style={styles.eventInfo}>
                <Text style={styles.eventNumber}>Event #{item.eventNumber}</Text>
                <Text style={styles.eventDescription}>{item.description}</Text>
                <Text
                    style={styles.eventMeta}>{new Date(item.startDateTime).toLocaleDateString()} - {item.format}</Text>
            </View>
        </TouchableOpacity>
    );

    if (loading) {
        return <ActivityIndicator style={styles.centered} size="large"/>;
    }

    const sections = sessions.map(session => ({
        title: session.name,
        data: session.events,
    }));

    return (
        <View style={styles.container}>
            <SectionList
                sections={sections}
                keyExtractor={(item) => item.id.toString()}
                renderItem={renderEvent}
                renderSectionHeader={({section: {title}}) => (
                    <Text style={styles.sectionHeader}>{title}</Text>
                )}
                ListEmptyComponent={<Text style={styles.empty}>No events found for this season.</Text>}
            />
        </View>
    );
};

const styles = StyleSheet.create({
    container: {flex: 1, backgroundColor: '#f5f5f5'},
    centered: {flex: 1, justifyContent: 'center', alignItems: 'center'},
    sectionHeader: {
        backgroundColor: '#e0e0e0',
        padding: 10,
        fontSize: 16,
        fontWeight: 'bold',
        color: '#333',
    },
    eventItem: {
        backgroundColor: '#fff',
        padding: 15,
        borderBottomWidth: 1,
        borderBottomColor: '#eee',
    },
    eventInfo: {flex: 1},
    eventNumber: {fontSize: 14, fontWeight: 'bold', color: '#007AFF'},
    eventDescription: {fontSize: 16, marginVertical: 2},
    eventMeta: {fontSize: 12, color: '#666'},
    empty: {textAlign: 'center', marginTop: 20, color: '#999'},
});

export default EventListScreen;
