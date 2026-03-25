import React, {useEffect, useState} from 'react';
import {View, Text, StyleSheet, ActivityIndicator, TouchableOpacity, ScrollView, Alert} from 'react-native';
import EventService from '../../services/EventService';
import {EventDetail} from '../../types';

const EventDetailScreen = ({route, navigation}: any) => {
    const {id, eventNumber} = route.params;
    const [event, setEvent] = useState<EventDetail | null>(null);
    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);

    useEffect(() => {
        navigation.setOptions({title: `Event #${eventNumber}`});
        loadEventDetail();
    }, [id]);

    const loadEventDetail = async () => {
        try {
            const data = await EventService.getEventDetail(id);
            setEvent(data);
        } catch (error) {
            console.error('Failed to load event detail', error);
            Alert.alert('Error', 'Could not load event details.');
        } finally {
            setLoading(false);
        }
    };

    const handleToggleRegistration = async () => {
        if (!event) return;
        setSubmitting(true);
        try {
            const result = await EventService.toggleRegistration(id);
            if (result.success) {
                setEvent({...event, isRegistered: result.isRegistered});
                Alert.alert('Success', result.message);
            }
        } catch (error) {
            console.error('Registration failed', error);
            Alert.alert('Error', 'Registration request failed.');
        } finally {
            setSubmitting(false);
        }
    };

    if (loading) {
        return <ActivityIndicator style={styles.centered} size="large"/>;
    }

    if (!event) {
        return (
            <View style={styles.centered}>
                <Text>Event not found.</Text>
            </View>
        );
    }

    return (
        <ScrollView style={styles.container} contentContainerStyle={styles.content}>
            <View style={styles.header}>
                <Text style={styles.eventNumber}>Event #{event.eventNumber}</Text>
                <Text style={styles.description}>{event.description}</Text>
            </View>

            <View style={styles.section}>
                <Text style={styles.label}>Date & Time</Text>
                <Text style={styles.value}>
                    {new Date(event.startDateTime).toLocaleString()}
                </Text>
            </View>

            <View style={styles.section}>
                <Text style={styles.label}>Course & Nine</Text>
                <Text style={styles.value}>{event.course} - {event.nine}</Text>
            </View>

            <View style={styles.section}>
                <Text style={styles.label}>Format</Text>
                <Text
                    style={styles.value}>{event.format} {event.isWithHandicapping ? '(Handicapped)' : '(No Handicap)'}</Text>
            </View>

            <TouchableOpacity
                style={[styles.button, styles.viewGamesButton]}
                onPress={() => navigation.navigate('GameList', {eventId: event.id, eventNumber: event.eventNumber})}
            >
                <Text style={styles.buttonText}>View Games</Text>
            </TouchableOpacity>

            <TouchableOpacity
                style={[styles.button, styles.resultsButton]}
                onPress={() => navigation.navigate('EventResults', {eventId: event.id, eventNumber: event.eventNumber})}
            >
                <Text style={styles.buttonText}>Show Results</Text>
            </TouchableOpacity>
        </ScrollView>
    );
};

const styles = StyleSheet.create({
    container: {flex: 1, backgroundColor: '#f5f5f5'},
    content: {padding: 20},
    centered: {flex: 1, justifyContent: 'center', alignItems: 'center'},
    header: {marginBottom: 30},
    eventNumber: {fontSize: 14, fontWeight: 'bold', color: '#007AFF'},
    description: {fontSize: 24, fontWeight: 'bold', color: '#333', marginTop: 5},
    section: {marginBottom: 20, paddingBottom: 15, borderBottomWidth: 1, borderBottomColor: '#eee'},
    label: {fontSize: 12, color: '#666', marginBottom: 5, textTransform: 'uppercase'},
    value: {fontSize: 18, color: '#333'},
    button: {
        marginTop: 20,
        padding: 15,
        borderRadius: 8,
        alignItems: 'center',
        justifyContent: 'center',
    },
    viewGamesButton: {backgroundColor: '#007AFF', marginBottom: 10},
    resultsButton: {backgroundColor: '#34C759'},
    buttonText: {color: '#fff', fontSize: 18, fontWeight: 'bold'},
});

export default EventDetailScreen;
